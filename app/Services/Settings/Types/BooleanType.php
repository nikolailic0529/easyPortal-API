<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class BooleanType extends Type {
    protected function fromNotNullString(string $value): bool {
        return $value === 'true' || $value === '(true)';
    }

    protected function toNotNullString(mixed $value): string {
        return $value === true ? 'true' : 'false';
    }
}
