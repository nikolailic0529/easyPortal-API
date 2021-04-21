<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\CurrentTenant;
use App\Models\User;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed;
use App\Services\KeyCloak\Exceptions\AuthorizationFailedStateMismatch;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Session\Session;

class KeyCloak {
    protected const STATE = 'keycloak.state';

    protected Provider $provider;

    public function __construct(
        protected Repository $config,
        protected Session $session,
        protected CurrentTenant $tenant,
    ) {
        // empty
    }

    public function getAuthorizationUrl(): string {
        $url   = $this->getProvider()->getAuthorizationUrl();
        $state = $this->getProvider()->getState();

        $this->session->put(self::STATE, $state);

        return $url;
    }

    public function authorize(string $code, string $state): User {
        // TODO: Create user if not exists

        // Is state valid?
        if ($this->session->pull(self::STATE) !== $state) {
            throw new AuthorizationFailedStateMismatch();
        }

        // Get Access Token
        try {
            $token = $this->getProvider()->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
        } catch (Exception $exception) {
            throw new AuthorizationFailed($exception);
        }

        throw new Exception('Not implemented.');
    }

    protected function getProvider(): Provider {
        if (!isset($this->provider)) {
            $this->provider = new Provider([
                'url'          => $this->config->get('ep.keycloak.url'),
                'realm'        => $this->config->get('ep.keycloak.realm'),
                'tenant'       => $this->tenant->get(),
                'clientId'     => $this->config->get('ep.keycloak.client_id'),
                'clientSecret' => $this->config->get('ep.keycloak.client_secret'),
                'redirectUri'  => $this->config->get('ep.keycloak.redirect_uri'),
            ]);
        }

        return $this->provider;
    }
}
