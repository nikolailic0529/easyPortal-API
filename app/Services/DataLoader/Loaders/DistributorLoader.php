<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\DistributorNotFoundException;
use App\Services\DataLoader\Factories\DistributorFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use Exception;
use Psr\Log\LoggerInterface;

class DistributorLoader extends CompanyLoader {
    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected DistributorFactory $distributors,
    ) {
        parent::__construct($logger, $client);
    }

    protected function getObjectFactory(): ModelFactory {
        return $this->distributors;
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new DistributorNotFoundException($id);
    }
}
