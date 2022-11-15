<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use Elastic\Client\ClientBuilder as BaseClientBuilder;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder as ClientBuilderFactory;
use ErrorException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

use function array_filter;
use function is_array;
use function is_string;
use function sprintf;

class ClientBuilder extends BaseClientBuilder {
    public function __construct(
        protected Container $container,
        protected Repository $config,
    ) {
        // empty
    }

    public function connection(string $name): Client {
        // Cached?
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        // Config
        $config = $this->config->get("elastic.client.connections.{$name}");

        if (!is_array($config)) {
            throw new ErrorException(sprintf(
                'Configuration for connection %s is invalid or missing.',
                $name,
            ));
        }

        // Prepare
        $config = array_filter($config, static fn(mixed $value): bool => $value !== null);
        $logger = $config['logger'] ?? null;

        if (is_string($logger)) {
            $logger = $this->container->make(LogManager::class)->channel($logger);
        } elseif ($logger === true) {
            $logger = $this->container->make(LoggerInterface::class);
        } else {
            $logger = null;
        }

        if ($logger instanceof LoggerInterface) {
            $config['logger'] = $logger;
        } else {
            unset($config['logger']);
        }

        // Create
        $client             = ClientBuilderFactory::fromConfig($config);
        $this->cache[$name] = $client;

        return $client;
    }
}
