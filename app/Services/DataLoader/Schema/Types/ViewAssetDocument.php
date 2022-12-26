<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\TextNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class ViewAssetDocument extends Type {
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

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $documentNumber;

    public ?ViewDocument $document;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $startDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $endDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $deletedAt;

    public ?ViewCompany $reseller;
    public ?ViewCompany $customer;
}
