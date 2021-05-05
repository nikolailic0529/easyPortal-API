<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\Models\Organization;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity;
use App\Services\KeyCloak\Exceptions\KeyCloakException;
use App\Services\KeyCloak\Exceptions\StateMismatch;
use App\Services\KeyCloak\Exceptions\UnknownScope;
use App\Services\Tenant\Tenant;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

use function strtr;

class KeyCloak {
    protected const STATE = 'keycloak.state';
    protected const TOKEN = 'keycloak.token';

    protected Provider $provider;

    public function __construct(
        protected Repository $config,
        protected Session $session,
        protected AuthManager $auth,
        protected Tenant $tenant,
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
            'scope' => $this->getOrganizationScopes($organization),
        ]);
        $state    = $provider->getState();

        $this->session->put(self::STATE, $state);

        return $url;
    }

    public function authorize(string $code, string $state): Authenticatable {
        // Is state valid?
        if ($this->session->pull(self::STATE) !== $state) {
            throw new StateMismatch();
        }

        // Get Access Token
        try {
            $token = $this->getProvider()->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
        } catch (Exception $exception) {
            throw new InvalidIdentity($exception);
        }

        // Attempt to sign in
        try {
            $result = $this->auth->guard()->attempt([
                UserProvider::ACCESS_TOKEN => $token->getToken(),
            ]);

            if ($result) {
                $this->saveToken($token);
            } else {
                throw new InvalidCredentials();
            }
        } catch (KeyCloakException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new AuthorizationFailed($exception);
        }

        // Return
        return $this->auth->guard()->user();
    }

    public function signOut(): ?string {
        // First we try to sign out without redirect
        $provider   = $this->getProvider();
        $successful = false;

        try {
            $token = $this->getToken();

            if ($token) {
                $successful = $provider->signOut($token);
            }
        } catch (Exception $exception) {
            $this->handler->report($exception);
        }

        // Next we destroy the active session
        $this->auth->guard()->logout();
        $this->forgetToken();

        // And the last step - redirect if sign out failed.
        $url = null;

        if (!$successful) {
            $url = $provider->getSignOutUrl([
                'redirect_uri' => $this->getRedirectUri(
                    $this->config->get('ep.keycloak.redirects.signout_uri'),
                    $this->tenant->get(),
                ),
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
    protected function getProvider(): Provider {
        if (!isset($this->provider)) {
            $this->provider = new Provider([
                'url'          => $this->config->get('ep.keycloak.url'),
                'realm'        => $this->config->get('ep.keycloak.realm'),
                'clientId'     => $this->config->get('ep.keycloak.client_id'),
                'clientSecret' => $this->config->get('ep.keycloak.client_secret'),
                'redirectUri'  => $this->getRedirectUri($this->config->get('ep.keycloak.redirects.signin_uri')),
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

    protected function getToken(): ?AccessTokenInterface {
        // TODO [KeyCloak] If token expired should we refresh it?
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
    // </editor-fold>
}
