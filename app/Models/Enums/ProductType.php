<?php declare(strict_types = 1);

namespace App\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class ProductType extends Enum {
    public static function asset(): static {
        return static::make(__FUNCTION__);
    }

    public static function support(): static {
        return static::make(__FUNCTION__);
    }

    public static function service(): static {
        return static::make(__FUNCTION__);
    }
}
