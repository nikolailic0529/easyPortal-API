<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\Tokens\OAuth2Token;
use App\Services\Tokens\Service;
use Illuminate\Contracts\Config\Repository;
use League\OAuth2\Client\Provider\AbstractProvider;

class Token extends OAuth2Token {
    public function __construct(
        protected Repository $config,
        Service $service,
    ) {
        parent::__construct($service);
    }

    protected function getProvider(): AbstractProvider {
        return $this->getGenericProvider(
            $this->config->get('ep.data_loader.url'),
            $this->config->get('ep.data_loader.client_id'),
            $this->config->get('ep.data_loader.client_secret'),
        );
    }

    /**
     * @inheritDoc
     */
    protected function getTokenParameters(): array {
        $parameters = parent::getTokenParameters();

        if ($this->config->get('ep.data_loader.resource')) {
            $parameters['resource'] = $this->config->get('ep.data_loader.resource');
        } elseif ($this->config->get('ep.data_loader.endpoint')) {
            $parameters['resource'] = $this->config->get('ep.data_loader.endpoint');
        } else {
            // empty
        }

        return $parameters;
    }
}
