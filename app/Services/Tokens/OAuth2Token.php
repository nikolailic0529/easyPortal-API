<?php declare(strict_types = 1);

namespace App\Services\Tokens;

use App\Services\Tokens\Exceptions\InvalidCredentials;
use Exception;
use Illuminate\Contracts\Cache\Repository;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

use function rtrim;

/**
 * Class encapsulates all logic related to obtaining OAuth 2.0 Access Token for
 * Client Credentials Grant.
 */
abstract class OAuth2Token {
    private ?AccessTokenInterface $token = null;

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

        if ($this->token) {
            $token = $this->token;
        } elseif ($this->getCache()->has($key)) {
            $token = new AccessToken($this->getCache()->get($key));
        } else {
            // empty
        }

        // Expired?
        if ($token && $token->hasExpired()) {
            $token = null;
        }

        // Nope or Expired -> get a new one
        if (!$token) {
            $token = $this->getToken();

            $this->getCache()->set($key, $token->jsonSerialize());
        }

        // Save
        $this->token = $token;

        // Return
        return $token->getToken();
    }

    public function reset(): static {
        $this->token = null;

        $this->getCache()->forget($this->key());

        return $this;
    }

    protected function key(): string {
        return $this::class;
    }

    protected function getToken(): AccessTokenInterface {
        try {
            $params = $this->getTokenParameters();
            $token  = $this->getProvider()->getAccessToken('client_credentials', $params);
        } catch (Exception $exception) {
            throw new InvalidCredentials($this::class, $exception);
        }

        return $token;
    }

    protected function getProvider(): AbstractProvider {
        $url      = rtrim($this->url, '/');
        $provider = new GenericProvider([
            'clientId'                => $this->clientId,
            'clientSecret'            => $this->clientSecret,
            'urlAuthorize'            => "{$url}/authorize",
            'urlAccessToken'          => "{$url}/token",
            'urlResourceOwnerDetails' => "{$url}/resource",
        ]);

        return $provider;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getTokenParameters(): array {
        return [];
    }

    protected function getCache(): Repository {
        return $this->getCache();
    }
}
