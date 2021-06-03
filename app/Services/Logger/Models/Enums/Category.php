<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Category extends Enum {
    public static function jobs(): static {
        return static::make('jobs');
    }

    public static function events(): static {
        return static::make('events');
    }

    public static function dataLoader(): static {
        return static::make('data-loader');
    }
}
