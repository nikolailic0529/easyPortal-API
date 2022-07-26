<?php declare(strict_types = 1);

namespace App\Services\Keycloak;

use App\Models\Organization;
use App\Services\Auth\Exceptions\AuthException;
use App\Services\Keycloak\Auth\UserProvider;
use App\Services\Keycloak\Exceptions\Auth\AuthorizationFailed;
use App\Services\Keycloak\Exceptions\Auth\InvalidCredentials;
use App\Services\Keycloak\Exceptions\Auth\InvalidIdentity;
use App\Services\Keycloak\Exceptions\Auth\StateMismatch;
use App\Services\Keycloak\Exceptions\Auth\UnknownScope;
use App\Services\Keycloak\Exceptions\KeycloakDisabled;
use App\Services\Keycloak\OAuth2\Provider;
use App\Services\Organization\CurrentOrganization;
use Config\Constants;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

use function implode;
use function strtr;

class Keycloak {
    protected const STATE = 'keycloak.state';
    protected const TOKEN = 'keycloak.token';

    protected Provider $provider;

    public function __construct(
        protected Repository $config,
        protected Session $session,
        protected AuthManager $auth,
        protected CurrentOrganization $organization,
        protected UrlGenerator $url,
        protected ExceptionHandler $handler,
    ) {
        // empty
    }

    // <editor-fold desc="Authorization">
    // =========================================================================
    public function getAuthorizationUrl(Organization $organization): string {
        $provider = $this->getProvider();
        $url      = $provider->getAuthorizationUrl([
            'scope'        => $this->getOrganizationScopes($organization),
            'redirect_uri' => $this->getSignInUri($organization),
        ]);
        $state    = $provider->getState();

        $this->saveAuthorizationState($organization, $state);

        return $url;
    }

    public function authorize(Organization $organization, string $code, string $state): ?Authenticatable {
        // Is state valid?
        if ($this->getAuthorizationState($organization) !== $state) {
            throw new StateMismatch();
        }

        // Get Access Token
        try {
            $token = $this->getProvider()->getAccessToken('authorization_code', [
                'code'         => $code,
                'redirect_uri' => $this->getSignInUri($organization),
            ]);
        } catch (Exception $exception) {
            throw new InvalidIdentity($exception);
        }

        // Attempt to sign in
        try {
            $result = $this->auth->guard()->attempt([
                UserProvider::CREDENTIAL_ACCESS_TOKEN => $token->getToken(),
                UserProvider::CREDENTIAL_ORGANIZATION => $organization,
            ]);

            if ($result) {
                $this->saveToken($token);
            } else {
                throw new InvalidCredentials();
            }
        } catch (AuthException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new AuthorizationFailed($exception);
        }

        // Return
        return $this->auth->guard()->user();
    }

    public function signIn(string $email, string $password): ?Authenticatable {
        // Attempt to sign in
        try {
            $result = $this->auth->guard()->attempt([
                UserProvider::CREDENTIAL_EMAIL    => $email,
                UserProvider::CREDENTIAL_PASSWORD => $password,
            ]);

            if (!$result) {
                throw new InvalidCredentials();
            }
        } catch (AuthException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new AuthorizationFailed($exception);
        }

        // Return
        return $this->auth->guard()->user();
    }

    public function signOut(): ?string {
        // Enabled?
        $provider = null;

        try {
            $provider = $this->getProvider();
        } catch (KeycloakDisabled) {
            // empty
        }

        // First we try to sign out without redirect
        $token      = null;
        $successful = false;

        try {
            $token      = $this->getToken();
            $successful = $token && $provider?->signOut($token);
        } catch (IdentityProviderException) {
            // Token can be expired or keycloak session is ended or etc. We are
            // not interested about the reason -> error can be ignored.
            $this->forgetToken();
        } catch (Exception $exception) {
            $this->handler->report($exception);
            $this->forgetToken();
        }

        // Next we destroy the active session
        //
        // `flush()` and `regenerateToken()` required to fix
        // https://github.com/laravel/framework/issues/37393
        // https://github.com/fakharanwar/easyPortal-API/issues/912
        $this->auth->guard()->logout();
        $this->session->flush();
        $this->session->regenerate(true);
        $this->forgetToken();

        // And the last step - redirect if sign out failed.
        $url = null;

        if ($provider && $token && !$successful && $this->organization->defined()) {
            $url = $provider->getSignOutUrl([
                'redirect_uri' => $this->getSignOutUri($this->organization->get()),
            ]);
        }

        return $url;
    }
    // </editor-fold>

    // <editor-fold desc="Getters">
    // =========================================================================
    public function getOrganizationScope(Organization $organization): string {
        return $organization->keycloak_scope
            ?: throw new UnknownScope($organization);
    }

    public function getValidIssuer(): string {
        return $this->getProvider()->getRealmUrl();
    }

    public function getClientId(): ?string {
        return $this->getProvider()->getClientId();
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function isEnabled(): bool {
        return $this->config->get('ep.keycloak.enabled')
            && $this->config->get('ep.keycloak.url')
            && $this->config->get('ep.keycloak.realm')
            && $this->config->get('ep.keycloak.client_id')
            && $this->config->get('ep.keycloak.client_secret');
    }

    public function getProvider(): Provider {
        if (!$this->isEnabled()) {
            throw new KeycloakDisabled();
        }

        if (!isset($this->provider)) {
            $this->provider = new Provider([
                'url'          => $this->config->get('ep.keycloak.url'),
                'realm'        => $this->config->get('ep.keycloak.realm'),
                'clientId'     => $this->config->get('ep.keycloak.client_id'),
                'clientSecret' => $this->config->get('ep.keycloak.client_secret'),
                'redirectUri'  => $this->getSignInUri(null),
            ]);
        }

        return $this->provider;
    }

    /**
     * @return array<string>
     */
    protected function getOrganizationScopes(Organization $organization): array {
        return [
            'openid',
            'email',
            'phone',
            'profile',
            "reseller_{$this->getOrganizationScope($organization)}",
        ];
    }

    protected function getRedirectUri(string $uri, Organization $organization = null): string {
        if ($organization) {
            $uri = strtr($uri, [
                '{organization}' => $organization->getKey(),
            ]);
        }

        return $this->url->to($uri);
    }

    protected function getToken(): ?AccessToken {
        // TODO [Keycloak] If token expired should we refresh it?
        $token = $this->session->has(self::TOKEN)
            ? new AccessToken($this->session->get(self::TOKEN))
            : null;

        return $token;
    }

    protected function saveToken(AccessTokenInterface $token): void {
        $this->session->put(self::TOKEN, $token->jsonSerialize());
    }

    protected function forgetToken(): void {
        $this->session->forget(self::TOKEN);
    }

    protected function getSignInUri(?Organization $organization): string {
        return $this->getRedirectUri(
            $this->config->get('ep.client.signin_uri') ?? Constants::EP_CLIENT_SIGNIN_URI,
            $organization,
        );
    }

    protected function getSignOutUri(?Organization $organization): string {
        return $this->getRedirectUri(
            $this->config->get('ep.client.signout_uri') ?? Constants::EP_CLIENT_SIGNOUT_URI,
            $organization,
        );
    }

    protected function saveAuthorizationState(Organization $organization, string $state): void {
        $this->session->put($this->getAuthorizationStateKey($organization), $state);
    }

    protected function getAuthorizationState(Organization $organization): mixed {
        return $this->session->pull($this->getAuthorizationStateKey($organization));
    }

    protected function getAuthorizationStateKey(Organization $organization): string {
        return implode(':', [self::STATE, $organization->getKey()]);
    }
    // </editor-fold>
}
