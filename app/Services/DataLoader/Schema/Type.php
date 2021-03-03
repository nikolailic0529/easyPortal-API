<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Utils\JsonFactory;
use ReflectionClass;

/**
 * @internal
 */
abstract class Type extends JsonFactory {
    /**
     * @return array<string>
     */
    public static function getPropertiesNames(): array {
        $properties = (new ReflectionClass(static::class))->getProperties();
        $names      = [];

        foreach ($properties as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }
}
