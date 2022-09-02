<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class DocumentEntry extends Type {
    public ?string $assetId;
    public ?string $serviceGroupSku;
    public ?string $serviceGroupSkuDescription;
    public ?string $serviceLevelSku;
    public ?string $serviceLevelSkuDescription;
    public ?string $startDate;
    public ?string $endDate;
    public ?string $currencyCode;
    public ?string $languageCode;
    public ?string $listPrice;
    public ?string $estimatedValueRenewal;
    public ?string $assetProductType;
    public ?string $environmentId;
    public ?string $equipmentNumber;
    public ?string $lineItemListPrice;
    public ?string $lineItemMonthlyRetailPrice;
    public ?string $said;
    public ?string $sarNumber;
}
