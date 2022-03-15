<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class ViewCompany extends Type {
    public string  $id;
    public string  $name;

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
