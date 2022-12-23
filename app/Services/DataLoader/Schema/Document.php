<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;

class Document extends Type implements TypeWithKey {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;

    public ?string $type;
    public ?string $documentNumber;
    public ?string $startDate;
    public ?string $endDate;
    public ?string $currencyCode;
    public ?string $totalNetPrice;
    public ?string $languageCode;
    public ?string $updatedAt;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $resellerId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $customerId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $distributorId;

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

    public function getKey(): string {
        return $this->id;
    }
}
