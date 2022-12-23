<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;

class Company extends Type implements TypeWithKey {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;

    public string  $name;
    public ?string $companyType;
    public ?string $updatedAt;
    public ?string $keycloakName;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
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
    public ?CompanyKpis  $companyKpis;

    /**
     * @var array<CompanyKpis>|null
     */
    #[JsonObjectArray(CompanyKpis::class)]
    public ?array $companyResellerKpis;

    /**
     * @var array<string>|null
     */
    public ?array $status;

    public function getKey(): string {
        return $this->id;
    }
}
