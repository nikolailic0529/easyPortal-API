<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Asset as AssetModel;
use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;

trait WithAssetDocument {
    use WithOem;
    use WithServiceGroup;
    use WithServiceLevel;

    protected function assetDocumentOem(AssetModel $asset, ViewAssetDocument $assetDocument): Oem {
        return isset($assetDocument->document)
            ? $this->documentOem($assetDocument->document)
            : $asset->oem;
    }

    protected function assetDocumentServiceGroup(AssetModel $asset, ViewAssetDocument $assetDocument): ?ServiceGroup {
        $oem   = $this->assetDocumentOem($asset, $assetDocument);
        $sku   = $assetDocument->supportPackage ?? null;
        $group = null;

        if ($oem && $sku) {
            $group = $this->serviceGroup($oem, $sku);
        }

        return $group;
    }

    protected function assetDocumentServiceLevel(AssetModel $asset, ViewAssetDocument $assetDocument): ?ServiceLevel {
        $oem   = $this->assetDocumentOem($asset, $assetDocument);
        $sku   = $assetDocument->skuNumber ?? null;
        $group = $this->assetDocumentServiceGroup($asset, $assetDocument);
        $level = null;

        if ($oem && $group && $sku) {
            $level = $this->serviceLevel($oem, $group, $sku);
        }

        return $level;
    }

    protected function documentOem(ViewDocument $document): Oem {
        return $this->oem($document->vendorSpecificFields->vendor);
    }
}
