<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Types\CompanyKpis;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use App\Services\DataLoader\Schema\Types\ViewDocument;

/**
 * @mixin Factory
 */
trait WithReseller {
    abstract protected function getResellerFinder(): ?ResellerFinder;

    abstract protected function getResellerResolver(): ResellerResolver;

    protected function reseller(Document|ViewAsset|ViewDocument|ViewAssetDocument|CompanyKpis $object): ?Reseller {
        // Id
        $id = null;

        if ($object instanceof ViewAssetDocument) {
            $id = $object->reseller->id ?? null;
        } else {
            $id = $object->resellerId ?? null;
        }

        // Search
        $reseller = null;

        if ($id) {
            $reseller = $this->getResellerResolver()->get($id, function () use ($id, $object): Reseller {
                $reseller = $this->getResellerFinder()?->find($id);

                if (!$reseller) {
                    throw new ResellerNotFound($id, $object);
                }

                return $reseller;
            });
        }

        // Return
        return $reseller;
    }
}
