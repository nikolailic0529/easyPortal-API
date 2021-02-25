<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Utils;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

use function array_map;
use function is_null;
use function preg_match;
use function str_contains;

/**
 * Convert JSON into an object, but be careful - this class doesn't worry about
 * property exists or not, is it initialized or not. Use it only for JSON with
 * a known structure when you 100% sure that it can be converted into an
 * object.
 */
abstract class JsonFactory {
    /**
     * Contains factories for properties that should be an instance of class or
     * an array of classes (this data extracted from type-hints and comments).
     *
     * @var array<string, callable>|null
     */
    private static array|null $properties = null;

    public function __construct() {
        // empty
    }

    /**
     * @param array<mixed> $json
     */
    public static function create(array $json): static {
        $object     = new static();
        $properties = static::getProperties();

        foreach ($json as $property => $value) {
            $object->{$property} = isset($properties[$property])
                ? $properties[$property]($value)
                : $value;
        }

        return $object;
    }

    /**
     * @return array<string, callable>
     */
    private static function getProperties(): array {
        if (is_null(static::$properties)) {
            $properties         = (new ReflectionClass(static::class))->getProperties();
            static::$properties = [];

            foreach ($properties as $property) {
                // Unions can not be processes
                $type = $property->getType();

                if (!($type instanceof ReflectionNamedType)) {
                    continue;
                }

                // Determine target class and create factory
                $class   = $type->getName();
                $isArray = false;
                $factory = null;

                if ($class === 'array') {
                    // @var array<Class>
                    // @var array<key, Class>
                    $regexp  = '/@var array\<(?:[^,]+,\s*)?(?P<class>[^>]+)\>/ui';
                    $comment = $property->getDocComment();
                    $matches = [];

                    if (preg_match($regexp, $comment, $matches)) {
                        $class   = $matches['class'];
                        $isArray = true;
                    }
                }

                if (str_contains($class, '\\')) {
                    $factory = static function (array $json) use ($class): object {
                        /** @var static $class */
                        return $class::create($json);
                    };

                    if ($isArray) {
                        $factory = static function (array $json) use ($factory): array {
                            return array_map($factory, $json);
                        };
                    }
                }

                // Save
                if ($factory) {
                    static::$properties[$property->getName()] = $factory;
                }
            }
        }

        return static::$properties;
    }

    /**
     * @return array<string>
     */
    public static function getPropertiesNames(): array {
        return array_map(static function (ReflectionProperty $property): string {
            return $property->getName();
        }, (new ReflectionClass(static::class))->getProperties());
    }
}
