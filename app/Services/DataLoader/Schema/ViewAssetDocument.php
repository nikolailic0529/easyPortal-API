<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class ViewAssetDocument extends Type {
    public ?string $serviceGroupSku;
    public ?string $serviceGroupSkuDescription;
    public ?string $serviceLevelSku;
    public ?string $serviceLevelSkuDescription;

    public ?string $warrantyEndDate;

    public ?string       $documentNumber;
    public ?ViewDocument $document;
    public ?string       $startDate;
    public ?string       $endDate;

    public ?string $currencyCode;
    public ?string $languageCode;
    public ?string $netPrice;
    public ?string $discount;
    public ?string $listPrice;

    public ?string $estimatedValueRenewal;

    public ?ViewCompany $reseller;
    public ?ViewCompany $customer;
}
