<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\Boolean;

class BooleanType extends Type {
    protected function fromNotNullString(string $value): bool {
        return $value === 'true' || $value === '(true)';
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
