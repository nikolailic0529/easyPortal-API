<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Utils\JsonFactory;
use ReflectionClass;
use ReflectionProperty;

use function array_map;

abstract class Type extends JsonFactory {
    /**
     * @return array<string>
     */
    public static function getPropertiesNames(): array {
        return array_map(static function (ReflectionProperty $property): string {
            return $property->getName();
        }, (new ReflectionClass(static::class))->getProperties());
    }
}
