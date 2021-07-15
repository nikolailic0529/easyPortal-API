<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class ViewDocument extends Type implements TypeWithId {
    public string                      $id;
    public string                      $type;
    public string                      $documentNumber;
    public ?string                     $startDate;
    public ?string                     $endDate;
    public ?string                     $currencyCode;
    public ?string                     $totalNetPrice;
    public ?string                     $languageCode;
    public ?string                     $updatedAt;
    public DocumentVendorSpecificField $vendorSpecificFields;
    /**
     * @var array<\App\Services\DataLoader\Schema\CompanyContactPerson>
     */
    public ?array $contactPersons;

    public ?string $resellerId;
    public ?string $customerId;
    public ?string $distributorId;
}
