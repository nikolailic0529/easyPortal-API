<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Customer;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Commands\UpdateCustomer;
use Exception;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;

class CustomerSync extends Sync {
    public function displayName(): string {
        return 'ep-data-loader-customer-sync';
    }

    public function init(Customer $customer): static {
        return $this
            ->setObjectId($customer->getKey())
            ->initialized();
    }

    /**
     * @return array{result: bool, warranty: bool}
     */
    public function __invoke(ExceptionHandler $handler, Kernel $kernel, Client $client): array {
        return [
            'warranty' => $this->checkWarranty($handler, $client),
            'result'   => $this->syncProperties($handler, $kernel),
        ];
    }

    protected function checkWarranty(ExceptionHandler $handler, Client $client): bool {
        try {
            return $client->runCustomerWarrantyCheck($this->getObjectId());
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }

    protected function syncProperties(ExceptionHandler $handler, Kernel $kernel): bool {
        try {
            return $this->isCommandSuccessful($kernel->call(UpdateCustomer::class, $this->getOptions([
                'interaction'      => false,
                'id'               => $this->getObjectId(),
                'assets'           => true,
                'assets-documents' => true,
            ])));
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }
}
