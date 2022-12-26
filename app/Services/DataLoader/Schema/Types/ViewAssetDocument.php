<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class ViewAssetDocument extends Type {
    public ?string $serviceGroupSku;
    public ?string $serviceGroupSkuDescription;
    public ?string $serviceLevelSku;
    public ?string $serviceLevelSkuDescription;

    #[JsonObjectNormalizer(TextNormalizer::class)]
    public ?string $serviceFullDescription;

    public ?string       $documentNumber;
    public ?ViewDocument $document;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $startDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $endDate;

    public ?string $deletedAt;

    public ?ViewCompany $reseller;
    public ?ViewCompany $customer;
}
