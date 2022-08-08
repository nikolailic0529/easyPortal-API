<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class CustomField extends Type {
    public string  $Name;
    public ?string $Value;
}
