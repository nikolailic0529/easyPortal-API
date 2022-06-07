<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class Document extends Type implements TypeWithId {
    public string                      $id;
    public ?string                     $type;
    public ?string                     $documentNumber;
    public ?string                     $startDate;
    public ?string                     $endDate;
    public ?string                     $currencyCode;
    public ?string                     $totalNetPrice;
    public ?string                     $languageCode;
    public ?string                     $updatedAt;
    public ?string                     $resellerId;
    public ?string                     $customerId;
    public ?string                     $distributorId;
    public DocumentVendorSpecificField $vendorSpecificFields;

    /**
     * @var array<CompanyContactPerson>
     */
    #[JsonObjectArray(CompanyContactPerson::class)]
    public ?array $contactPersons;

    /**
     * @var array<DocumentEntry>
     */
    #[JsonObjectArray(DocumentEntry::class)]
    public ?array $documentEntries;

    /**
     * @var array<string>|null
     */
    public ?array $status;
}
