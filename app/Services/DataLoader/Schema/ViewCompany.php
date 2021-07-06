<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class ViewCompany extends Type {
    public string  $id;
    public string  $name;

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
}