<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class CompanyKpis extends Type {
    public ?int   $totalAssets;
    public ?int   $activeAssets;
    public ?float $activeAssetsPercentage;
    public ?int   $activeCustomers;
    public ?int   $newActiveCustomers;
    public ?int   $activeContracts;
    public ?float $activeContractTotalAmount;
    public ?int   $newActiveContracts;
    public ?int   $expiringContracts;
    public ?int   $activeQuotes;
    public ?float $activeQuotesTotalAmount;
    public ?int   $newActiveQuotes;
    public ?int   $expiringQuotes;
}
