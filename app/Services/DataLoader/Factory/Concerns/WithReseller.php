<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;

/**
 * @mixin Factory
 */
trait WithReseller {
    abstract protected function getNormalizer(): Normalizer;

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
            $id       = $this->getNormalizer()->uuid($id);
            $reseller = $this->getResellerResolver()->get($id, function () use ($id): ?Reseller {
                return $this->getResellerFinder()?->find($id);
            });
        }

        // Found?
        if ($id && !$reseller) {
            throw new ResellerNotFound($id, $object);
        }

        // Return
        return $reseller;
    }
}
