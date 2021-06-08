<?php declare(strict_types = 1);

namespace App\Utils;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionObject;

use function array_map;
use function count;
use function is_array;
use function is_object;
use function preg_match;
use function sprintf;
use function str_contains;

/**
 * Convert JSON into an object, but be careful - this class doesn't worry about
 * property exists or not, is it initialized or not. Use it only for JSON with
 * a known structure when you 100% sure that it can be converted into an
 * object.
 *
 * Features/Limitations (and TODOs)
 * - Type unions not supported;
 *
 * @internal
 */
abstract class JsonObject implements JsonSerializable, Arrayable, Countable {
    /**
     * Contains factories for properties that should be an instance of class or
     * an array of classes (this data extracted from type-hints and comments).
     *
     * @var array<string, array<string, callable>>|null
     */
    private static array $properties = [];

    /**
     * @param array<mixed> $json
     */
    public function __construct(array $json = []) {
        if ($json) {
            $properties = self::getDefinedProperties();

            foreach ($json as $property => $value) {
                $this->{$property} = isset($properties[$property])
                    ? $properties[$property]($value)
                    : $value;
            }
        }
    }

    // <editor-fold desc="API">
    // =========================================================================
    public function isEmpty(): bool {
        return $this->count() === 0;
    }

    /**
     * @return array<string,mixed>
     */
    public function getProperties(): array {
        $properties = (new ReflectionObject($this))->getProperties();
        $json       = [];

        foreach ($properties as $property) {
            if (!$property->isStatic() && $property->isInitialized($this)) {
                $json[$property->getName()] = $property->getValue($this);
            }
        }

        return $json;
    }
    // </editor-fold>

    // <editor-fold desc="Countable">
    // =========================================================================
    public function count(): int {
        return count($this->getProperties());
    }
    // </editor-fold>

    // <editor-fold desc="JsonSerializable">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array {
        return $this->getProperties();
    }
    // </editor-fold>

    // <editor-fold desc="Arrayable">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function toArray(): array {
        return $this->toArrayProcess($this->getProperties());
    }

    /**
     * @param array<string,mixed> $properties
     *
     * @return array<string,mixed>
     */
    private function toArrayProcess(array $properties): array {
        foreach ($properties as $name => $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (is_array($value)) {
                $properties[$name] = $this->toArrayProcess($value);
            } else {
                $properties[$name] = $value;
            }
        }

        return $properties;
    }
    // </editor-fold>

    // <editor-fold desc="Magic">
    // =========================================================================
    public function __get(string $name): mixed {
        throw new InvalidArgumentException(sprintf(
            'Property `%s::$%s` doesn\'t exist.',
            $this::class,
            $name,
        ));
    }

    public function __set(string $name, mixed $value): void {
        $this->__get($name);
    }

    public function __isset(string $name): bool {
        return isset($this->{$name});
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    /**
     * @return array<string, callable>
     */
    private static function getDefinedProperties(): array {
        if (!isset(self::$properties[static::class])) {
            $properties                      = (new ReflectionClass(static::class))->getProperties();
            self::$properties[static::class] = [];

            foreach ($properties as $property) {
                // Static properties should be ignored
                if ($property->isStatic()) {
                    continue;
                }

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
                    $factory = static function (object|array|null $json) use ($class): ?object {
                        /** @var static $class */
                        return is_object($json) ? $json : ($json !== null ? new $class($json) : null);
                    };

                    if ($isArray) {
                        $factory = static function (array|null $json) use ($factory): ?array {
                            return $json !== null ? array_map($factory, $json) : null;
                        };
                    }
                }

                // Save
                if ($factory) {
                    self::$properties[static::class][$property->getName()] = $factory;
                }
            }
        }

        return self::$properties[static::class];
    }
    // </editor-fold>
}
