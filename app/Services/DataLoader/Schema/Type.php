<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonFactory;
use ReflectionClass;

abstract class Type extends JsonFactory {
    /**
     * @deprecated Please use `new MyType([])` instead.
     *
     * @param array<mixed> $json
     */
    public static function create(array $json): static {
        return new static($json);
    }

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
