<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class ViewAsset extends Type implements TypeWithId {
    public string  $id;
    public ?string $resellerId;
    public ?string $customerId;
    public ?string $serialNumber;
    public ?string $productDescription;
    public ?string $assetTag;
    public ?string $assetType;
    public string  $vendor;
    public string  $sku;
    public ?string $eolDate;
    public ?string $eosDate;
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

    /**
     * @var array<\App\Services\DataLoader\Schema\ViewAssetDocument>
     */
    public array $assetDocument;

    /**
     * @var array<\App\Services\DataLoader\Schema\CompanyContactPerson>|null
     */
    public ?array $latestContactPersons;

    public ?ViewCompany         $reseller;
    public ?ViewCompany         $customer;
    public ?CoverageStatusCheck $coverageStatusCheck;
}
