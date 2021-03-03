<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Loader;
use Psr\Log\LoggerInterface;

use function var_dump;

/**
 * @internal
 */
class CustomerLoader extends Loader {
    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected CustomerFactory $factory,
    ) {
        parent::__construct($logger, $client);
    }

    public function load(string $id): bool {
        $company  = $this->client->getCompanyById($id);
        $customer = $this->factory->create($company);

        return (bool) $customer;
    }
}
