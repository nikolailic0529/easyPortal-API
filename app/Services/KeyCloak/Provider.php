<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\Models\Organization;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

use function ltrim;
use function rtrim;

/**
 * KeyCloak Provider.
 *
 * Based on {@see https://github.com/stevenmaguire/oauth2-keycloak}
 */
class Provider extends AbstractProvider {
    protected string       $url;
    protected string       $realm;
    protected Organization $tenant;

    /**
     * @param array<mixed> $options
     * @param array<mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = []) {
        parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl(): string {
        return $this->getRealmUrl('protocol/openid-connect/auth');
    }

    /**
     * @inheritDoc
     */
    public function getBaseAccessTokenUrl(array $params): string {
        return $this->getRealmUrl('protocol/openid-connect/token');
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string {
        return $this->getRealmUrl('protocol/openid-connect/userinfo');
    }

    /**
     * @param array<mixed> $options
     */
    public function getLogoutUrl(array $options = []): string {
        $base   = $this->getRealmUrl('protocol/openid-connect/logout');
        $params = $this->getAuthorizationParameters($options);
        $query  = $this->getAuthorizationQuery($params);
        $url    = $this->appendQuery($base, $query);

        return $url;
    }

    /**
     * @return array<string>
     */
    protected function getDefaultScopes(): array {
        return ['openid', $this->tenant->keycloak_scope];
    }

    /**
     * @inheritDoc
     */
    protected function checkResponse(ResponseInterface $response, $data) {
        if (!empty($data['error'])) {
            $error = $data['error'];

            if (isset($data['error_description'])) {
                $error .= ': '.$data['error_description'];
            }

            throw new IdentityProviderException($error, 0, $data);
        }
    }

    /**
     * @param array<mixed> $response
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface {
        return new ResourceOwner($response);
    }

    protected function getScopeSeparator(): string {
        return ' ';
    }

    protected function getRealmUrl(string $path): string {
        $url  = rtrim($this->url, '/');
        $path = ltrim($path, '/');

        return "{$url}/auth/realms/{$this->getRealm()}/{$path}";
    }

    protected function getRealm(): string {
        return $this->realm;
    }
}
