<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class Asset extends Type {
    public string   $id;
    public ?string  $resellerId;
    public ?Company $reseller;
    public ?string  $customerId;
    public ?Company $customer;
    public ?string  $serialNumber;
    public string   $productDescription;
    public ?string  $description;
    public string   $assetType;
    public string   $vendor;
    public string   $sku;
    public ?string  $eolDate;
    public ?string  $eosDate;
    public ?string  $zip;
    public ?string  $city;
    public ?string  $address;
    public ?string  $address2;

    /**
     * @var array<\App\Services\DataLoader\Schema\AssetDocument>
     */
    public array $assetDocument;
}
