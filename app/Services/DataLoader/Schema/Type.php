<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject;
use ReflectionClass;

abstract class Type extends JsonObject {
    public function getName(): string {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * @deprecated Please use `new MyType([])` instead.
     *
     * @param array<mixed> $json
     */
    public static function create(array $json): static {
        return new static($json);
    }

    /**
     * @deprecated Please use `jsonSerialize()` or `toArray()` instead.
     *
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
