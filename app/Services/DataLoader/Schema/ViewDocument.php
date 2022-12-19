<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class ViewDocument extends Type implements TypeWithKey {
    public string                      $id;
    public ?string                     $type;
    public ?string                     $documentNumber;
    public ?string                     $startDate;
    public ?string                     $endDate;
    public ?string                     $currencyCode;
    public ?string                     $languageCode;
    public ?string                     $updatedAt;
    public DocumentVendorSpecificField $vendorSpecificFields;
    /**
     * @var array<CompanyContactPerson>
     */
    #[JsonObjectArray(CompanyContactPerson::class)]
    public ?array $contactPersons;

    public ?string $resellerId;
    public ?string $customerId;
    public ?string $distributorId;

    public function getKey(): string {
        return $this->id;
    }
}
