<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\Boolean;

use function filter_var;

use const FILTER_VALIDATE_BOOLEAN;

class BooleanType extends Type {
    protected function fromNotNullString(string $value): bool {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function toNotNullString(mixed $value): string {
        return $value === true ? 'true' : 'false';
    }

    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [new Boolean()];
    }

    public function getName(): string {
        return 'Boolean';
    }
}
