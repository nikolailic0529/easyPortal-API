<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class DocumentEntry extends Type {
    public ?string $assetId;
    public ?string $skuNumber;
    public ?string $skuDescription;
    public ?string $supportPackage;
    public ?string $supportPackageDescription;
    public ?string $startDate;
    public ?string $endDate;
    public ?string $currencyCode;
    public ?string $languageCode;
    public ?string $netPrice;
    public ?string $discount;
    public ?string $listPrice;
    public ?string $estimatedValueRenewal;
}
