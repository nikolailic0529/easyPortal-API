<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class CompanyContactPerson extends Type {
    public string|null $phoneNumber;
    public string|null $name;
    public string      $type;
}
