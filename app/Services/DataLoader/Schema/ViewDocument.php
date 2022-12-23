<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;

class ViewDocument extends Type implements TypeWithKey {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;

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

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $resellerId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $customerId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $distributorId;

    public function getKey(): string {
        return $this->id;
    }
}
