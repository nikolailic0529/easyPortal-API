<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use ReflectionClass;

use function mb_strlen;
use function mb_substr;
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
        return $value;
    }

    public function toString(mixed $value): string {
        return (string) $value;
    }
}
