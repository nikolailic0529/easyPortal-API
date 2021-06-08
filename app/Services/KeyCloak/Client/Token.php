<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Services\KeyCloak\Provider;
use App\Services\Tokens\OAuth2Token;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

use function rtrim;

class Token extends OAuth2Token {
    public function __construct(
        protected ConfigRepository $config,
        CacheRepository $cache,
    ) {
        parent::__construct(
            $this->config->get('ep.keycloak.url'),
            $this->config->get('ep.keycloak.client_id'),
            $this->config->get('ep.keycloak.client_secret'),
            $cache,
        );
    }

    protected function getProvider(): Provider {
        if (!isset($this->provider)) {
            $url            = rtrim($this->url, '/');
            $this->provider = new Provider([
                'url'          => $url,
                'realm'        => $this->getRealm(),
                'clientId'     => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);
        }
        return $this->provider;
    }

    protected function getRealm(): string {
        return $this->config->get('ep.keycloak.realm');
    }
}
