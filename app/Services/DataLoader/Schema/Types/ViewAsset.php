<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UnsignedIntNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class ViewAsset extends Type implements TypeWithKey {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $resellerId;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $customerId;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $serialNumber;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetSkuDescription;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetTag;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetType;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $vendor;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $assetSku;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $eolDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $eosDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $eoslDate;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $zip;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $city;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $address;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $address2;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $country;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $latitude;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $longitude;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $countryCode;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $status;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $updatedAt;

    /**
     * @var array<string>|null
     */
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?array $assetCoverage;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $dataQualityScore;

    #[JsonObjectNormalizer(UnsignedIntNormalizer::class)]
    public ?int $activeContractQuantitySum;

    /**
     * @var array<ViewAssetDocument>
     */
    #[JsonObjectArray(ViewAssetDocument::class)]
    public array $assetDocument;

    /**
     * @var array<CompanyContactPerson>|null
     */
    #[JsonObjectArray(CompanyContactPerson::class)]
    public ?array $latestContactPersons;

    public ?ViewCompany         $reseller;
    public ?ViewCompany         $customer;
    public ?CoverageStatusCheck $coverageStatusCheck;

    public function getKey(): string {
        return $this->id;
    }
}
