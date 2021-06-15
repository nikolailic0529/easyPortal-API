<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use Illuminate\Support\Collection;
use ReflectionClass;

use function is_null;
use function preg_match;

/**
 * Assign setting type to GraphQL scalar type.
 */
abstract class Type {
    public function getName(): string {
        return (new ReflectionClass($this))->getShortName();
    }

    public function fromString(string $value): mixed {
        return $this->isNull($value) ? null : $this->fromNotNullString($value);
    }

    public function toString(mixed $value): string {
        return is_null($value) ? 'null' : $this->toNotNullString($value);
    }

    protected function fromNotNullString(string $value): mixed {
        // Unwraps string in "string" or 'string'
        return preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)
            ? $matches[2]
            : $value;
    }

    protected function toNotNullString(mixed $value): string {
        return (string) $value;
    }

    protected function isNull(string $value): bool {
        return $value === 'null' || $value === '(null)';
    }

    /**
     * @return array<string|\Illuminate\Validation\Rule>
     */
    public function getValidationRules(): array {
        return [];
    }

    /**
     * @return \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model>|array<mixed>|null
     */
    public function getValues(): Collection|array|null {
        return null;
    }
}
