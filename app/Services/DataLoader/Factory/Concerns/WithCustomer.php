<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;

/**
 * @mixin Factory
 */
trait WithCustomer {
    abstract protected function getNormalizer(): Normalizer;

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
            $id       = $this->getNormalizer()->uuid($id);
            $customer = $this->getCustomerResolver()->get($id, function () use ($id): ?Customer {
                return $this->getCustomerFinder()?->find($id);
            });
        }

        // Found?
        if ($id && !$customer) {
            throw new CustomerNotFound($id, $object);
        }

        // Return
        return $customer;
    }
}
