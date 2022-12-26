<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class ViewDocument extends Type implements TypeWithKey {
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

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $languageCode;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $updatedAt;

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
