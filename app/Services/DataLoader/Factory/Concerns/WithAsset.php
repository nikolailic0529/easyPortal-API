<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Asset;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Schema\Types\DocumentEntry;

/**
 * @mixin Factory
 */
trait WithAsset {
    abstract protected function getAssetFinder(): ?AssetFinder;

    abstract protected function getAssetResolver(): AssetResolver;

    protected function asset(DocumentEntry $object): ?Asset {
        // Id
        $id = $object->assetId ?? null;

        // Search
        $asset = null;

        if ($id) {
            $asset = $this->getAssetResolver()->get($id, function (?Asset $asset) use ($id, $object): Asset {
                $asset ??= $this->getAssetFinder()?->find($id);

                if (!$asset) {
                    throw new AssetNotFound($id, $object);
                }

                return $asset;
            });
        }

        // Return
        return $asset;
    }
}
