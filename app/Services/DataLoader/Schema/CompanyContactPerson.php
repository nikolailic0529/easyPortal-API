<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

/**
 * @internal
 */
class CompanyContactPerson extends Type {
    public string $phoneNumber;
    public string $vendor;
    public string $name;
    public string $type;
}
