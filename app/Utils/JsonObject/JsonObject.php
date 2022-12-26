<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use Countable;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionObject;

use function array_map;
use function count;
use function explode;
use function in_array;
use function is_a;
use function is_array;
use function is_object;
use function preg_match;
use function reset;
use function sprintf;

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
 *
 * @implements Arrayable<string,mixed>
 */
abstract class JsonObject implements JsonSerializable, Arrayable, Countable {
    /**
     * Contains factories for properties that should be an instance of class or
     * an array of classes (this data extracted from type-hints and comments).
     *
     * @var array<string, array<string, callable>>
     */
    private static array $properties = [];

    /**
     * @param array<string, mixed> $json
     */
    final public function __construct(array $json = []) {
        if ($json) {
            $properties = self::getDefinedProperties();

            foreach ($json as $property => $value) {
                $this->{$property} = isset($properties[$property])
                    ? $properties[$property]($value)
                    : $value;
            }
        }
    }

    /**
     * @param array<int, array<string,mixed>>|null $objects
     *
     * @return array<static>
     */
    public static function make(array|null $objects): array|null {
        $result = null;

        if (is_array($objects)) {
            $result = array_map(static function (array $object): static {
                return new static($object);
            }, $objects);
        }

        return $result;
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
                    // We need to know the type of array items, but there is no
                    // robust way to determine it :( It can be a short class
                    // name and in this case we need to parse `use` statements
                    // and find required class, but this is difficult, moreover
                    // I cannot find any robust package for this. So we are
                    // using the custom attribute and additional checks to
                    // ensure that it is using the same type as in phpdoc.
                    //
                    // @var array<Class>
                    // @var array<key, Class>
                    // @var array<key, array<Class>>
                    // @var array<key, class-string<Class>>
                    $attributes = $property->getAttributes(JsonObjectArray::class);
                    $attribute  = reset($attributes);
                    $isArray    = true;
                    $matches    = [];
                    $comment    = $property->getDocComment() ?: '';
                    $regexp     = '/@var array\<(?:[^,]+,\s*)?(?P<type>[^<]+)(\<.+?\>)\>/ui';
                    $class      = $attribute instanceof ReflectionAttribute
                        ? $attribute->newInstance()->getType()
                        : null;

                    if (preg_match($regexp, $comment, $matches)) {
                        $type    = $matches['type'];
                        $scalars = ['int', 'float', 'string', 'bool', 'mixed', 'class-string', 'array'];
                        $invalid = $class
                            ? Arr::last(explode('\\', $class)) !== $type
                            : !in_array($type, $scalars, true);

                        if ($invalid) {
                            throw new LogicException('Impossible to determine type of array items.');
                        }
                    }
                }

                if ($class === DateTimeInterface::class) {
                    $factory = static function (DateTimeInterface|string|null $json): ?DateTimeInterface {
                        return is_object($json) ? $json : ($json !== null ? Date::make($json) : null);
                    };
                } elseif ($class && is_a($class, self::class, true)) {
                    $factory = static function (object|array|null $json) use ($class): ?object {
                        /** @var static $class */
                        return is_object($json) ? $json : ($json !== null ? new $class($json) : null);
                    };
                } else {
                    $normalizer = $property->getAttributes(JsonObjectNormalizer::class);
                    $normalizer = (reset($normalizer) ?: null)?->newInstance();

                    if ($normalizer instanceof JsonObjectNormalizer) {
                        $factory = static function (mixed $value) use ($normalizer): mixed {
                            return $normalizer->normalize($value);
                        };
                    }
                }

                if ($factory && $isArray) {
                    $factory = static function (array|null $json) use ($factory): ?array {
                        return $json !== null ? array_map($factory, $json) : null;
                    };
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
