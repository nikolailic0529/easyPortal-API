<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class BooleanType extends Type {
    public function fromString(string $value): bool {
        return $value === 'true';
    }

    public function toString(mixed $value): string {
        return $value === true ? 'true' : 'false';
    }
}
