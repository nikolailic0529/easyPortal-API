<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class CentralAssetDbStatistics extends Type {
    public ?int $assetsAmount;
    public ?int $documentsAmount;
    public ?int $documentsContractAmount;
    public ?int $documentsQuoteAmount;
    public ?int $companiesAmount;
    public ?int $companiesResellerAmount;
    public ?int $companiesCustomerAmount;
    public ?int $companiesDistributorAmount;
}
