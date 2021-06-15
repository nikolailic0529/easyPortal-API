<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class StringType extends Type {
    protected function fromNotNullString(string $value): string {
        return $value === 'empty' || $value === '(empty)' ? '' : $value;
    }

    /**
     * @return array<mixed>
     */
    public function getValidationRules(): array {
        return [];
    }

    public function getName(): string {
        return $this::class === self::class ? 'String' : parent::getName();
    }
}
