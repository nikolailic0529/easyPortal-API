<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\CurrentTenant;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity;
use App\Services\KeyCloak\Exceptions\KeyCloakException;
use App\Services\KeyCloak\Exceptions\StateMismatch;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

class KeyCloak {
    protected const STATE = 'keycloak.state';

    protected Provider $provider;

    public function __construct(
        protected Repository $config,
        protected Session $session,
        protected AuthManager $auth,
        protected UrlGenerator $url,
        protected CurrentTenant $tenant,
    ) {
        // empty
    }

    // <editor-fold desc="Authorization">
    // =========================================================================
    public function getAuthorizationUrl(): string {
        $url   = $this->getProvider()->getAuthorizationUrl();
        $state = $this->getProvider()->getState();

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

            if (!$result) {
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

    public function signOut(): string {
        $this->auth->guard()->logout();

        return $this->getProvider()->getSignOutUrl();
    }
    // </editor-fold>

    // <editor-fold desc="Getters">
    // =========================================================================
    public function getTenant(): CurrentTenant {
        return $this->tenant;
    }

    public function getTenantScope(): string {
        return Str::snake($this->getTenant()->get()->name, '-');
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
                'redirectUri'  => $this->url->to($this->config->get('ep.keycloak.redirect_uri')),
                'scopes'       => [
                    'openid',
                    'email',
                    'phone',
                    'profile',
                    "reseller_{$this->getTenantScope()}",
                ],
            ]);
        }

        return $this->provider;
    }
    // </editor-fold>
}
