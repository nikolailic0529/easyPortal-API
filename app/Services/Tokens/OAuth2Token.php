<?php declare(strict_types = 1);

namespace App\Services\Tokens;

use App\Services\Tokens\Exceptions\InvalidCredentials;
use Exception;
use Illuminate\Contracts\Cache\Repository;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

use function rtrim;

/**
 * Class encapsulates all logic related to obtaining OAuth 2.0 Access Token for
 * Client Credentials Grant.
 */
abstract class OAuth2Token {
    protected function __construct(
        protected string $url,
        protected string $clientId,
        protected string $clientSecret,
        protected Repository $cache,
    ) {
        // empty
    }

    public function getAccessToken(): string {
        // Cached?
        $key   = $this->key();
        $token = null;

        if ($this->cache->has($key)) {
            $token = new AccessToken($this->cache->get($key));

            if ($token->hasExpired()) {
                $token = null;
            }
        }

        // Nope or Expired -> get a new one
        if (!$token) {
            $token = $this->getToken();

            $this->cache->set($key, $token->jsonSerialize());
        }

        // Return
        return $token->getToken();
    }

    public function reset(): static {
        $this->cache->forget($this->key());

        return $this;
    }

    protected function key(): string {
        return $this::class;
    }

    protected function getToken(): AccessTokenInterface {
        $url      = rtrim($this->url, '/');
        $provider = new GenericProvider([
            'clientId'                => $this->clientId,
            'clientSecret'            => $this->clientSecret,
            'urlAuthorize'            => "{$url}/authorize",
            'urlAccessToken'          => "{$url}/token",
            'urlResourceOwnerDetails' => "{$url}/resource",
        ]);

        try {
            $token = $provider->getAccessToken('client_credentials', $this->getTokenParameters());
        } catch (Exception $exception) {
            throw new InvalidCredentials($this::class, $exception);
        }

        return $token;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getTokenParameters(): array {
        return [];
    }
}
