<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class Company extends Type implements TypeWithKey {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $name;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $companyType;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $updatedAt;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $keycloakName;

    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public ?string $keycloakGroupId;

    #[JsonObjectNormalizer(StringNormalizer::class)]
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
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?array $status;

    public function getKey(): string {
        return $this->id;
    }
}
