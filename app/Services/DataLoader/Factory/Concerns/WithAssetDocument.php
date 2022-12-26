<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Asset as AssetModel;
use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use App\Services\DataLoader\Schema\Types\ViewDocument;

trait WithAssetDocument {
    use WithOem;
    use WithServiceGroup;
    use WithServiceLevel;

    abstract protected function getOemResolver(): OemResolver;

    protected function assetDocumentOem(AssetModel $asset, ViewAssetDocument $assetDocument): ?Oem {
        return isset($assetDocument->document)
            ? $this->documentOem($assetDocument->document)
            : $this->getOemResolver()->getByKey($asset->oem_id);
    }

    protected function assetDocumentServiceGroup(AssetModel $asset, ViewAssetDocument $assetDocument): ?ServiceGroup {
        $oem   = $this->assetDocumentOem($asset, $assetDocument);
        $sku   = $assetDocument->serviceGroupSku ?? null;
        $name  = $assetDocument->serviceGroupSkuDescription ?? null;
        $group = null;

        if ($oem && $sku) {
            $group = $this->serviceGroup($oem, $sku, $name);
        }

        return $group;
    }

    protected function assetDocumentServiceLevel(AssetModel $asset, ViewAssetDocument $assetDocument): ?ServiceLevel {
        $oem   = $this->assetDocumentOem($asset, $assetDocument);
        $sku   = $assetDocument->serviceLevelSku ?? null;
        $group = $this->assetDocumentServiceGroup($asset, $assetDocument);
        $level = null;

        if ($oem && $group && $sku) {
            $name  = $assetDocument->serviceLevelSkuDescription ?? null;
            $desc  = $assetDocument->serviceFullDescription ?? null;
            $level = $this->serviceLevel($oem, $group, $sku, $name, $desc);
        }

        return $level;
    }

    protected function documentOem(Document|ViewDocument $document): ?Oem {
        return $this->oem($document->vendorSpecificFields->vendor);
    }
}
