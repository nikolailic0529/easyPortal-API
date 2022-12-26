<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Schema\Type;

class CompanyContactPerson extends Type {
    public string|null $phoneNumber;
    public string|null $name;
    public string|null $vendor;
    public string|null $mail;
    public string      $type;
}
