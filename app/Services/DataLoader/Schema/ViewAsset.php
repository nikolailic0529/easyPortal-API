<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class ViewAsset extends Type implements TypeWithKey {
    public string  $id;
    public ?string $resellerId;
    public ?string $customerId;
    public ?string $serialNumber;
    public ?string $assetSkuDescription;
    public ?string $assetTag;
    public ?string $assetType;
    public ?string $vendor;
    public ?string $assetSku;
    public ?string $eolDate;
    public ?string $eosDate;
    public ?string $eoslDate;
    public ?string $zip;
    public ?string $city;
    public ?string $address;
    public ?string $address2;
    public ?string $country;
    public ?string $latitude;
    public ?string $longitude;
    public ?string $countryCode;
    public string  $status;
    public ?string $updatedAt;

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
