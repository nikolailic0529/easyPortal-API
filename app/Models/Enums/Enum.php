<?php declare(strict_types = 1);

namespace App\Models\Enums;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use ReflectionMethod;

use function array_filter;
use function array_keys;
use function count;
use function gettype;
use function in_array;
use function is_int;
use function is_null;
use function is_string;
use function sprintf;

abstract class Enum implements Castable {
    /**
     * @var array<class-string<static>,array<string,static>>
     */
    private static array $instances = [];
    /**
     * @var array<class-string<static>,array<string|int>>
     */
    private static array $values = [];
    private static bool  $lookup = false;

    private function __construct(
        private string|int $value,
    ) {
        // empty
    }

    // <editor-fold desc="API">
    // =========================================================================
    public function getValue(): string {
        return $this->value;
    }

    /**
     * @return array<string|int>
     */
    public static function values(): array {
        if (!isset(self::$values[static::class])) {
            // Get list of available methods
            $class   = new ReflectionClass(static::class);
            $methods = $class->getMethods();
            $methods = array_filter($methods, static function (ReflectionMethod $method): bool {
                return $method->isStatic()
                    && $method->isPublic()
                    && $method->getDeclaringClass()->getName() !== self::class
                    && count($method->getParameters()) === 0;
            });

            // Get values
            try {
                self::$lookup = true;

                foreach ($methods as $method) {
                    $method->invoke(null);
                }
            } finally {
                self::$lookup = false;
            }

            self::$values[static::class] = array_keys(self::$instances[static::class]);
        }

        return self::$values[static::class];
    }

    protected static function create(string|int $value): static {
        // Valid?
        if (!self::$lookup && !in_array($value, static::values(), true)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` is not a valid value for `%s` enum.',
                $value,
                static::class,
            ));
        }

        // Created?
        if (!isset(self::$instances[static::class][$value])) {
            self::$instances[static::class][$value] = new static($value);
        }

        // Return
        return self::$instances[static::class][$value];
    }
    // </editor-fold>

    // <editor-fold desc="CastsAttributes">
    // =========================================================================
    /**
     * @inheritdoc
     */
    public static function castUsing(array $arguments): CastsAttributes {
        $class   = static::class;
        $factory = static function (string|int $value): static {
            return static::create($value);
        };

        return new class($class, $factory) implements CastsAttributes {
            /**
             * @param class-string<\App\Models\Enums\Enum> $enum
             */
            public function __construct(
                private string $enum,
                private Closure $factory,
            ) {
                // empty
            }

            /**
             * @inheritdoc
             */
            public function get($model, string $key, $value, array $attributes): ?Enum {
                if (is_null($value) || $value instanceof $this->enum) {
                    // no action
                } elseif (is_string($value) || is_int($value)) {
                    $value = ($this->factory)($value);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'The `%s` cannot be converted into `%s` enum.',
                        gettype($value),
                        $this->enum,
                    ));
                }

                return $value;
            }

            /**
             * @inheritdoc
             */
            public function set($model, string $key, $value, array $attributes): string|int|null {
                if (!is_null($value)) {
                    $value = $this->get($model, $key, $value, $attributes)?->getValue();
                }

                return $value;
            }
        };
    }
    // </editor-fold>
}
