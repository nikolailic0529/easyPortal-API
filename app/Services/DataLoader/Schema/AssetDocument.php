<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class AssetDocument extends Type {
    public string $skuNumber;
    public string $skuDescription;

    public string $supportPackage;
    public string $supportPackageDescription;

    public ?string $warrantyEndDate;

    public string    $documentId;
    public ?Document $document;
    public string    $startDate;
    public string    $endDate;
}
