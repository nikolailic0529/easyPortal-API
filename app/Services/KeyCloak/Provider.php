<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

use function ltrim;
use function rtrim;

/**
 * KeyCloak Provider.
 *
 * Based on {@see https://github.com/stevenmaguire/oauth2-keycloak}
 */
class Provider extends AbstractProvider {
    use BearerAuthorizationTrait;

    protected string $url;
    protected string $realm;

    /**
     * @var array<string>
     */
    protected array $scopes;

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
    public function getSignOutUrl(array $options = []): string {
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
        return $this->scopes;
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

    public function getRealmUrl(string $path = ''): string {
        $path = ltrim($path, '/');
        $url  = rtrim($this->url, '/');
        $url  = rtrim("{$url}/auth/realms/{$this->getRealm()}/{$path}", '/');

        return $url;
    }

    protected function getRealm(): string {
        return $this->realm;
    }
}
