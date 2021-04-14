<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use ReflectionClass;

use function is_null;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function str_ends_with;

/**
 * Assign setting type to GraphQL scalar type.
 */
abstract class Type {
    public function getName(): string {
        $name   = (new ReflectionClass($this))->getShortName();
        $suffix = 'Type';

        if (str_ends_with($name, $suffix)) {
            $length = mb_strlen($name) - mb_strlen($suffix);

            if ($length > 0) {
                $name = mb_substr($name, 0, $length);
            }
        }

        return $name;
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
}
