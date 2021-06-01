<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Status extends Enum {
    public static function active(): static {
        return static::make(__FUNCTION__);
    }

    public static function success(): static {
        return static::make(__FUNCTION__);
    }

    public static function failed(): static {
        return static::make(__FUNCTION__);
    }
}
