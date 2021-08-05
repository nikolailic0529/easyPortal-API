<?php declare(strict_types = 1);

namespace App\Services\Audit\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Action extends Enum {
    public static function created(): static {
        return static::make(__FUNCTION__);
    }

    public static function updated(): static {
        return static::make(__FUNCTION__);
    }

    public static function deleted(): static {
        return static::make(__FUNCTION__);
    }
}
