<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Loader;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class CustomerLoader extends Loader {
    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected CustomerFactory $customers,
        protected LocationFactory $locations,
        protected ContactFactory $contacts,
    ) {
        parent::__construct($logger, $client);
    }

    public function load(string $id): bool {
        $company  = $this->client->getCompanyById($id);
        $customer = null;

        if ($company) {
            $customer = $this->customers->create($company);
        }

        return (bool) $customer;
    }

    public function withLocations(bool $with): static {
        $this->customers->setLocationFactory($with ? $this->locations : null);

        return $this;
    }

    public function withContacts(bool $with): static {
        $this->customers->setContactsFactory($with ? $this->contacts : null);

        return $this;
    }
}
