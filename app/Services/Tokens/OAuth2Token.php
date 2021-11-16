<?php declare(strict_types = 1);

namespace App\Services\Tokens;

use App\Services\Tokens\Exceptions\InvalidCredentials;
use App\Utils\CacheKeyable;
use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

use function rtrim;

/**
 * Class encapsulates all logic related to obtaining OAuth 2.0 Access Token for
 * Client Credentials Grant.
 */
abstract class OAuth2Token implements CacheKeyable {
    private ?AccessTokenInterface $token = null;

    protected function __construct(
        protected Service $service,
    ) {
        // empty
    }

    public function getAccessToken(): string {
        // Cached?
        $token = null;

        if ($this->token) {
            $token = $this->token;
        } else {
            $token = $this->getService()->get($this, static function (array $token): AccessToken {
                return new AccessToken($token);
            });
        }

        // Expired?
        if ($token && $token->hasExpired()) {
            $token = null;
        }

        // Nope or Expired -> get a new one
        if (!$token) {
            $token = $this->getService()->set($this, $this->getToken());
        }

        // Save
        $this->token = $token;

        // Return
        return $token->getToken();
    }

    public function reset(): static {
        $this->token = null;

        $this->getService()->delete($this);

        return $this;
    }

    protected function getToken(): AccessTokenInterface {
        try {
            $params = $this->getTokenParameters();
            $token  = $this->getProvider()->getAccessToken('client_credentials', $params);
        } catch (Exception $exception) {
            throw (new InvalidCredentials($this::class, $exception))->setChannel(Service::getService($this));
        }

        return $token;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getTokenParameters(): array {
        return [];
    }

    abstract protected function getProvider(): AbstractProvider;

    protected function getGenericProvider(string $url, string $clientId, string $clientSecret): AbstractProvider {
        $url      = rtrim($url, '/');
        $provider = new GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'urlAuthorize'            => "{$url}/authorize",
            'urlAccessToken'          => "{$url}/token",
            'urlResourceOwnerDetails' => "{$url}/resource",
        ]);

        return $provider;
    }

    protected function getService(): Service {
        return $this->service;
    }
}
