<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;

class ViewCompany extends Type {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;
    public string $name;

    /**
     * @var array<CompanyContactPerson>
     */
    #[JsonObjectArray(CompanyContactPerson::class)]
    public array $companyContactPersons;
    /**
     * @var array<CompanyType>
     */
    #[JsonObjectArray(CompanyType::class)]
    public array $companyTypes;
    /**
     * @var array<Location>
     */
    #[JsonObjectArray(Location::class)]
    public array $locations;
}
