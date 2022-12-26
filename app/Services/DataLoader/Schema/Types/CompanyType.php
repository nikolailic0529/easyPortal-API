<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Schema\Type;

class CompanyType extends Type {
    public string $type;
    public string $status;
}
