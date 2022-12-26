<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;

class ViewCompany extends Type {
    #[JsonObjectNormalizer(UuidNormalizer::class)]
    public string $id;

    #[JsonObjectNormalizer(StringNormalizer::class)]
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
