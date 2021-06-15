<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Services\KeyCloak\KeyCloak;
use App\Services\KeyCloak\Provider;
use App\Services\Tokens\OAuth2Token;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class Token extends OAuth2Token {
    public function __construct(
        protected ConfigRepository $config,
        protected KeyCloak $keyCloak,
        CacheRepository $cache,
    ) {
        parent::__construct(
            (string) $this->config->get('ep.keycloak.url'),
            (string) $this->config->get('ep.keycloak.client_id'),
            (string) $this->config->get('ep.keycloak.client_secret'),
            $cache,
        );
    }

    protected function getProvider(): Provider {
        return $this->keyCloak->getProvider();
    }
}
