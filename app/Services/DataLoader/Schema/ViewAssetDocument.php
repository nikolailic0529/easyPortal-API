<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class ViewAssetDocument extends Type {
    public ?string $serviceGroupSku;
    public ?string $serviceGroupSkuDescription;
    public ?string $serviceLevelSku;
    public ?string $serviceLevelSkuDescription;
    public ?string $serviceFullDescription;

    public ?string       $documentNumber;
    public ?ViewDocument $document;
    public ?string       $startDate;
    public ?string       $endDate;
    public ?string       $deletedAt;

    public ?ViewCompany $reseller;
    public ?ViewCompany $customer;
}
