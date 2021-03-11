<?php declare(strict_types = 1);

namespace App;

use Illuminate\Support\Env;
use LogicException;

use function array_shift;
use function constant;
use function defined;

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
}
