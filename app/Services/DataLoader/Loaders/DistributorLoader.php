<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Factories\DistributorFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

class DistributorLoader extends CompanyLoader {
    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Client $client,
        protected DistributorFactory $distributors,
    ) {
        parent::__construct($container, $exceptionHandler, $client);
    }

    protected function getObjectFactory(): ModelFactory {
        return $this->distributors;
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new DistributorNotFound($id);
    }
}
