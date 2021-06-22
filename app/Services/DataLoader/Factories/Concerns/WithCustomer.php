<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Customer;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithCustomer {
    abstract protected function getCustomerFinder(): ?CustomerFinder;

    abstract protected function getCustomerResolver(): CustomerResolver;

    protected function customer(ViewAsset|ViewDocument|ViewAssetDocument $object): ?Customer {
        // Id
        $id = null;

        if ($object instanceof ViewAssetDocument) {
            $id = $object->customer->id ?? null;
        } else {
            $id = $object->customerId ?? null;
        }

        // Search
        $customer = null;

        if ($id) {
            $customer = $this->getCustomerResolver()->get($id, $this->factory(
                function () use ($id): ?Customer {
                    return $this->getCustomerFinder()?->find($id);
                },
            ));
        }

        // Found?
        if ($id && !$customer) {
            throw new CustomerNotFoundException($id, $object);
        }

        // Return
        return $customer;
    }
}
