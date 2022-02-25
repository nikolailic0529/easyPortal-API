<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Models\Asset;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAssetDocument;

class AssetDocumentObject extends Type {
    public Asset             $asset;
    public ViewAssetDocument $document;
}
