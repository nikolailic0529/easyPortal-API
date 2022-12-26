<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\DecimalNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class Document extends Type implements TypeWithKey {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $type;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $documentNumber;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $startDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $endDate;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $currencyCode;

    #[JsonObjectNormalizer(DecimalNormalizer::class)]
    public ?string $totalNetPrice;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $languageCode;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $updatedAt;

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
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?array $status;

    public function getKey(): string {
        return $this->id;
    }
}
