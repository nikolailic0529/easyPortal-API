<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Utils\JsonObject\JsonObjectNormalizer;

class DocumentEntry extends Type {
    public ?string $assetDocumentId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $assetId;

    public ?string $serviceGroupSku;
    public ?string $serviceGroupSkuDescription;
    public ?string $serviceLevelSku;
    public ?string $serviceLevelSkuDescription;
    public ?string $serviceFullDescription;
    public ?string $startDate;
    public ?string $endDate;
    public ?string $currencyCode;
    public ?string $languageCode;
    public ?string $listPrice;
    public ?string $estimatedValueRenewal;
    public ?string $assetProductType;
    public ?string $assetProductLine;
    public ?string $assetProductGroupDescription;
    public ?string $environmentId;
    public ?string $equipmentNumber;
    public ?string $lineItemListPrice;
    public ?string $lineItemMonthlyRetailPrice;
    public ?string $said;
    public ?string $sarNumber;
    public ?string $pspId;
    public ?string $pspName;
    public ?string $deletedAt;
}
