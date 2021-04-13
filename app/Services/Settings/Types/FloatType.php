<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\FloatNumber;

use function filter_var;
use function number_format;

use const FILTER_VALIDATE_FLOAT;

class FloatType extends Type {
    protected function fromNotNullString(string $value): float {
        return (float) filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    protected function toNotNullString(mixed $value): string {
        return number_format($value, 2, '.', '');
    }

    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [new FloatNumber()];
    }
}
