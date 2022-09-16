<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Customer;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Loader\Loaders\CustomerLoader;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Exception;
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
    public function __invoke(ExceptionHandler $handler, Client $client, CustomerLoader $loader): array {
        return GlobalScopes::callWithoutAll(function () use ($handler, $client, $loader): array {
            $warranty = $this->checkWarranty($handler, $client);
            $result   = $this->syncProperties($handler, $loader, $warranty);

            return [
                'warranty' => $warranty,
                'result'   => $result,
            ];
        });
    }

    protected function checkWarranty(ExceptionHandler $handler, Client $client): bool {
        try {
            return $client->runCustomerWarrantyCheck($this->getObjectId());
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }

    protected function syncProperties(ExceptionHandler $handler, CustomerLoader $loader, bool $assets): bool {
        try {
            return $loader
                ->setObjectId($this->getObjectId())
                ->setWithDocuments(true)
                ->setWithAssets($assets)
                ->setWithWarrantyCheck(false)
                ->start();
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }
}
