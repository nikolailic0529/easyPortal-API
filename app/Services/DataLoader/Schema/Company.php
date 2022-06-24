<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class Company extends Type implements TypeWithId {
    public string  $id;
    public string  $name;
    public ?string $companyType;
    public ?string $updatedAt;
    public ?string $keycloakName;
    public ?string $keycloakGroupId;
    public ?string $keycloakClientScopeName;
    /**
     * @var array<CompanyContactPerson>
     */
    #[JsonObjectArray(CompanyContactPerson::class)]
    public array $companyContactPersons;
    /**
     * @var array<Location>
     */
    #[JsonObjectArray(Location::class)]
    public array $locations;
    /**
     * @var array<ViewAsset>
     */
    #[JsonObjectArray(ViewAsset::class)]
    public array $assets;

    public ?BrandingData $brandingData;
    public ?CompanyKpis $companyKpis;

    /**
     * @var array<CompanyKpis>|null
     */
    #[JsonObjectArray(CompanyKpis::class)]
    public ?array $companyResellerKpis;

    /**
     * @var array<string>|null
     */
    public ?array $status;
}
