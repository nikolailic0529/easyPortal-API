<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\DecimalNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\TextNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class DocumentEntry extends Type {
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetDocumentId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $assetId;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $serviceGroupSku;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $serviceGroupSkuDescription;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $serviceLevelSku;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $serviceLevelSkuDescription;

    #[JsonObjectNormalizer(TextNormalizer::class)]
    public ?string $serviceFullDescription;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $startDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $endDate;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $currencyCode;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $languageCode;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $listPrice;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $estimatedValueRenewal;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetProductType;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetProductLine;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetProductGroupDescription;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $environmentId;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $equipmentNumber;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $lineItemListPrice;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $lineItemMonthlyRetailPrice;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $said;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $sarNumber;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $pspId;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $pspName;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $deletedAt;
}
