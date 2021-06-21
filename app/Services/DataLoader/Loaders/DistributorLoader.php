<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Distributor;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\DistributorNotFoundException;
use App\Services\DataLoader\Factories\DistributorFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Finders\DistributorFinder;
use Exception;
use Psr\Log\LoggerInterface;

class DistributorLoader extends CompanyLoader implements DistributorFinder {
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

    // <editor-fold desc="DistributorFinder">
    // =========================================================================
    public function find(string $key): ?Distributor {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->create($key);
    }
    // </editor-fold>
}
