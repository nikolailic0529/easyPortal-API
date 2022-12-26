<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\FloatNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UnsignedFloatNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UnsignedIntNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;

class CompanyKpis extends Type {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $resellerId;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $totalAssets;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeAssets;

    #[JsonObjectNormalizer(UnsignedFloatNormalizer::class)]
    public ?float $activeAssetsPercentage;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeCustomers;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $newActiveCustomers;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeContracts;

    #[JsonObjectNormalizer(UnsignedFloatNormalizer::class)]
    public ?float $activeContractTotalAmount;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $newActiveContracts;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $expiringContracts;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeQuotes;

    #[JsonObjectNormalizer(UnsignedFloatNormalizer::class)]
    public ?float $activeQuotesTotalAmount;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $newActiveQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $expiringQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $expiredQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $expiredContracts;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $orderedQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $acceptedQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $requestedQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $receivedQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $rejectedQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $awaitingQuotes;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeAssetsOnContract;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeAssetsOnWarranty;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeExposedAssets;

    #[JsonObjectNormalizer(UnsignedFloatNormalizer::class)]
    public ?float $serviceRevenueTotalAmount;

    #[JsonObjectNormalizer(FloatNormalizer::class)]
    public ?float $serviceRevenueTotalAmountChange;
}
