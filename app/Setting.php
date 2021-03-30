<?php declare(strict_types = 1);

namespace App;

use Illuminate\Support\Env;
use LogicException;

use function array_map;
use function array_shift;
use function constant;
use function defined;
use function explode;
use function is_string;
use function trim;

/**
 * Special helper to get proper config value.
 *
 * MUST be used only inside config/*.php!
 *
 * Settings priorities:
 * - env variable
 * - constant
 */
class Setting {
    /**
     * @throws \LogicException if setting with given name not found.
     */
    public static function get(string ...$names): mixed {
        if (!$names) {
            throw new LogicException('Setting not found.');
        }

        $names = (array) $names;
        $name  = array_shift($names);

        return Env::get($name, static function () use ($name, $names) {
            return defined($name) ? constant($name) : static::get(...$names);
        });
    }

    /**
     * @throws \LogicException if setting with given name not found.
     *
     * @return array<mixed>
     */
    public static function getArray(string ...$names): ?array {
        $value = static::get(...$names);

        if (is_string($value)) {
            $value = array_map(static function (string $value): string {
                return trim($value);
            }, explode(',', $value));
        } else {
            $value = (array) $value;
        }

        return $value;
    }
}
