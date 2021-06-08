<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Models\Organization;
use App\Services\KeyCloak\Client\Exceptions\EndpointException;
use App\Services\KeyCloak\Client\Exceptions\InvalidKeyCloakGroup;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakDisabled;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\KeyCloak\Provider;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\Factory;

use function array_map;
use function rtrim;

class Client {

    public function __construct(
        protected Factory $client,
        protected Repository $config,
        protected ExceptionHandler $handler,
        protected Token $token,
    ) {
        // empty
    }

    // <editor-fold desc="endpoints">
    // =========================================================================
    /**
     * @return array<\App\Services\KeyCloak\Types\User>
     */
    public function users(Organization $organization): array {
        // GET {realm}/groups/{id}/members
        if (!$organization->keycloak_group_id) {
            throw new InvalidKeyCloakGroup();
        }

        $endpoint = "groups/{$organization->keycloak_group_id}/members";
        $result   = $this->call($endpoint);
        $result   = array_map(static function ($item) {
            return new User($item);
        }, $result);

        return $result;
    }

    // </editor-fold>

    // <editor-fold desc="API">
    // =========================================================================
    protected function getBaseUrl(): string {
        $keycloak = rtrim($this->config->get('ep.keycloak.url'), '/');
        $realm    = $this->config->get('ep.keycloak.realm');
        return "{$keycloak}/auth/admin/realms/{$realm}";
    }

    protected function isEnabled(): bool {
        return $this->config->get('ep.keycloak.url')
            && $this->config->get('ep.keycloak.client_id')
            && $this->config->get('ep.keycloak.client_secret');
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function call(string $endpoint, array $options = []): mixed {
        // Enabled?
        if (!$this->isEnabled()) {
            throw new KeyCloakDisabled();
        }
        $timeout     = $this->config->get('ep.keycloak.timeout') ?: 5 * 60;
        $accessToken = $this->token->getAccessToken();
        $headers     = [
            'Accept'        => 'application/json',
            'Authorization' => "Bearer {$accessToken}",
        ];

        $request = $this->client
            ->baseUrl($this->getBaseUrl())
            ->timeout($timeout);

        try {
            $response = $request->withHeaders($headers)->get($endpoint, $options);
            $response->throw();
        } catch (Exception $exception) {
            $error = new EndpointException($endpoint, $exception);
            $this->handler->report($error);
            throw $error;
        }

        return $response->json();
    }

    // </editor-fold>

    // <editor-fold desc="Authorization">
    // =========================================================================
    protected function getProvider(): Provider {
        if (!isset($this->provider)) {
            $this->provider = new Provider([
                'url'          => $this->config->get('ep.keycloak.url'),
                'realm'        => $this->config->get('ep.keycloak.realm'),
                'clientId'     => $this->config->get('ep.keycloak.client_id'),
                'clientSecret' => $this->config->get('ep.keycloak.client_secret'),
            ]);
        }

        return $this->provider;
    }
    // </editor-fold>
}
