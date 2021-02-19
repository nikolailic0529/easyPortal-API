<?php declare(strict_types = 1);

namespace App\Services\Auth0;

use Auth0\SDK\API\Authentication;
use Auth0\SDK\API\Management as Api;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

class Management {
    protected Config $config;
    protected Cache  $cache;
    protected ?Api   $api = null;

    public function __construct(Config $config, Cache $cache) {
        $this->config = $config;
        $this->cache  = $cache;
    }

    /**
     * @see \Auth0\SDK\API\Management\Users::create()
     *
     * @param array<mixed> $data
     *
     * @return array{user_id: string, picture: string}
     */
    public function createUser(array $data): array {
        // FIXME [auth0] Specify connection
        $data['connection'] ??= $this->config->get('laravel-auth0.connection');

        // FIXME [auth0] Tenant probably required here
        return $this->getApi()->users()->create($data);
    }

    protected function getApi(): Api {
        if (!$this->api) {
            $token         = $this->getAccessToken();
            $domain        = $this->config->get('laravel-auth0.domain');
            $guzzleOptions = $this->config->get('laravel-auth0.guzzle_options', []);
            $this->api     = new Api($token, $domain, $guzzleOptions);
        }

        return $this->api;
    }

    protected function getAccessToken(): string {
        // FIXME [auth0] Token request probably should be in job.
        $key   = __METHOD__;
        $token = $this->cache->get($key);

        if (!$token) {
            $service = new Authentication(
                $this->config->get('laravel-auth0.domain'),
                $this->config->get('laravel-auth0.api_client_id'),
                $this->config->get('laravel-auth0.api_client_secret'),
                $this->config->get('laravel-auth0.api_identifier'),
                null,
                $this->config->get('laravel-auth0.guzzle_options', []),
            );

            $token = $service->client_credentials([])['access_token'];

            $this->cache->set($key, $token, 3600);
        }

        return $token;
    }
}
