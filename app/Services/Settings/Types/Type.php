<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use ReflectionClass;

/**
 * Assign setting type to GraphQL scalar type.
 */
abstract class Type {
    public function getName(): string {
        return (new ReflectionClass($this))->getShortName();
    }
}
