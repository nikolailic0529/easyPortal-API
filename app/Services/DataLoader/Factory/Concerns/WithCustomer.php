<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use App\Services\DataLoader\Schema\Types\ViewDocument;

/**
 * @mixin Factory
 */
trait WithCustomer {
    abstract protected function getCustomerFinder(): ?CustomerFinder;

    abstract protected function getCustomerResolver(): CustomerResolver;

    protected function customer(Document|ViewAsset|ViewDocument|ViewAssetDocument $object): ?Customer {
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
            $customer = $this->getCustomerResolver()->get(
                $id,
                function (?Customer $customer) use ($id, $object): Customer {
                    $customer ??= $this->getCustomerFinder()?->find($id);

                    if (!$customer) {
                        throw new CustomerNotFound($id, $object);
                    }

                    return $customer;
                },
            );
        }

        // Return
        return $customer;
    }
}
