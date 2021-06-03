<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Category extends Enum {
    public static function queue(): static {
        return static::make('queue');
    }

    public static function eloquent(): static {
        return static::make('eloquent');
    }

    public static function dataLoader(): static {
        return static::make('data-loader');
    }
}
