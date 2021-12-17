<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class Company extends Type implements TypeWithId {
    public string  $id;
    public string  $name;
    public ?string $updatedAt;
    public ?string $keycloakName;
    public ?string $keycloakGroupId;
    public ?string $keycloakClientScopeName;
    /**
     * @var array<\App\Services\DataLoader\Schema\CompanyContactPerson>
     */
    public array $companyContactPersons;
    /**
     * @var array<\App\Services\DataLoader\Schema\CompanyType>
     */
    public array $companyTypes;
    /**
     * @var array<\App\Services\DataLoader\Schema\Location>
     */
    public array $locations;
    /**
     * @var array<\App\Services\DataLoader\Schema\ViewAsset>
     */
    public array $assets;

    public ?BrandingData $brandingData;
    public ?CompanyKpis $companyKpis;

    /**
     * @var array<\App\Services\DataLoader\Schema\CompanyKpis>|null
     */
    public ?array $companyResellerKpis;

    /**
     * @var array<string>|null
     */
    public ?array $status;
}
