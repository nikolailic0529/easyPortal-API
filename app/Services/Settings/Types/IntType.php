<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use function filter_var;

use const FILTER_VALIDATE_INT;

class IntType extends Type {
    protected function fromNotNullString(string $value): int {
        return (int) filter_var($value, FILTER_VALIDATE_INT);
    }
}
