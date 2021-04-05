<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use function filter_var;
use function number_format;

use const FILTER_VALIDATE_FLOAT;

class FloatType extends Type {
    public function fromString(string $value): float {
        return (float) filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    public function toString(mixed $value): string {
        return number_format($value, 2, '.', '');
    }
}
