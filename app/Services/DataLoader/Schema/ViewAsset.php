<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
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

    public ?string $serialNumber;
    public ?string $assetSkuDescription;
    public ?string $assetTag;
    public ?string $assetType;
    public ?string $vendor;
    public ?string $assetSku;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $eolDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $eosDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $eoslDate;
    public ?string $zip;
    public ?string $city;
    public ?string $address;
    public ?string $address2;
    public ?string $country;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $latitude;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $longitude;

    public ?string $countryCode;
    public string  $status;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $updatedAt;

    /**
     * @var array<string>|null
     */
    public ?array $assetCoverage;
    public string $dataQualityScore;
    public ?int   $activeContractQuantitySum;

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
