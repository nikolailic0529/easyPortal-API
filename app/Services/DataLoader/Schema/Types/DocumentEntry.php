<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\DecimalNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class DocumentEntry extends Type {
    public ?string $assetDocumentId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $assetId;

    public ?string $serviceGroupSku;
    public ?string $serviceGroupSkuDescription;
    public ?string $serviceLevelSku;
    public ?string $serviceLevelSkuDescription;

    #[JsonObjectNormalizer(TextNormalizer::class)]
    public ?string $serviceFullDescription;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $startDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $endDate;

    public ?string $currencyCode;
    public ?string $languageCode;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $listPrice;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $estimatedValueRenewal;

    public ?string $assetProductType;
    public ?string $assetProductLine;
    public ?string $assetProductGroupDescription;
    public ?string $environmentId;
    public ?string $equipmentNumber;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $lineItemListPrice;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $lineItemMonthlyRetailPrice;

    public ?string $said;
    public ?string $sarNumber;
    public ?string $pspId;
    public ?string $pspName;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $deletedAt;
}
