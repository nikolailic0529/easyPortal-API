<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\Tokens\OAuth2Token;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class Token extends OAuth2Token {
    public function __construct(
        protected ConfigRepository $config,
        CacheRepository $cache,
    ) {
        parent::__construct(
            $this->config->get('ep.data_loader.url'),
            $this->config->get('ep.data_loader.client_id'),
            $this->config->get('ep.data_loader.client_secret'),
            $cache,
        );
    }

    /**
     * @inheritDoc
     */
    protected function getTokenParameters(): array {
        $parameters = parent::getTokenParameters();

        if ($this->config->get('ep.data_loader.endpoint')) {
            $parameters['resource'] = $this->config->get('ep.data_loader.endpoint');
        }

        return $parameters;
    }
}
