<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Type;

class AssetDocumentObject extends Type {
    public Asset         $asset;
    public AssetDocument $document;

    /**
     * @var array<\App\Services\DataLoader\Schema\AssetDocument>
     */
    public array $entries;
}
