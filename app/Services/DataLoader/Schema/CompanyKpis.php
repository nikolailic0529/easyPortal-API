<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Utils\JsonObject\JsonObjectNormalizer;

class CompanyKpis extends Type {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $resellerId;

    public ?int    $totalAssets;
    public ?int    $activeAssets;
    public ?float  $activeAssetsPercentage;
    public ?int    $activeCustomers;
    public ?int    $newActiveCustomers;
    public ?int    $activeContracts;
    public ?float  $activeContractTotalAmount;
    public ?int    $newActiveContracts;
    public ?int    $expiringContracts;
    public ?int    $activeQuotes;
    public ?float  $activeQuotesTotalAmount;
    public ?int    $newActiveQuotes;
    public ?int    $expiringQuotes;
    public ?int    $expiredQuotes;
    public ?int    $expiredContracts;
    public ?int    $orderedQuotes;
    public ?int    $acceptedQuotes;
    public ?int    $requestedQuotes;
    public ?int    $receivedQuotes;
    public ?int    $rejectedQuotes;
    public ?int    $awaitingQuotes;
    public ?int    $activeAssetsOnContract;
    public ?int    $activeAssetsOnWarranty;
    public ?int    $activeExposedAssets;
    public ?float  $serviceRevenueTotalAmount;
    public ?float  $serviceRevenueTotalAmountChange;
}
