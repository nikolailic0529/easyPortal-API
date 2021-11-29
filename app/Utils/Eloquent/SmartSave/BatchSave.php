<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

use Closure;

class BatchSave {
    /**
     * @internal
     */
    public static ?BatchInsert $instance = null;
    /**
     * @internal
     */
    protected static bool $enabled = false;

    public static function isEnabled(): bool {
        return static::$enabled;
    }

    public static function enable(Closure $closure): mixed {
        $previous        = static::$enabled;
        static::$enabled = true;

        try {
            return $closure();
        } finally {
            static::$enabled = $previous;
        }
    }
}
