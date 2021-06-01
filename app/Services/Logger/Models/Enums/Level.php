<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

/**
 * @see \Psr\Log\LogLevel
 */
class Level extends Enum {
    public static function emergency(): static {
        return static::make(__FUNCTION__);
    }

    public static function alert(): static {
        return static::make(__FUNCTION__);
    }

    public static function critical(): static {
        return static::make(__FUNCTION__);
    }

    public static function error(): static {
        return static::make(__FUNCTION__);
    }

    public static function warning(): static {
        return static::make(__FUNCTION__);
    }

    public static function notice(): static {
        return static::make(__FUNCTION__);
    }

    public static function info(): static {
        return static::make(__FUNCTION__);
    }

    public static function debug(): static {
        return static::make(__FUNCTION__);
    }
}
