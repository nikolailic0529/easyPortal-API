<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Factories\DistributorFactory;
use App\Services\DataLoader\Factory;
use Psr\Log\LoggerInterface;

class DistributorLoader extends CompanyLoader {
    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected DistributorFactory $distributors,
    ) {
        parent::__construct($logger, $client);
    }

    protected function getCompanyFactory(): Factory {
        return $this->distributors;
    }
}
