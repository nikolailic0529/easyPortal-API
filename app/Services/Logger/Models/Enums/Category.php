<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Category extends Enum {
    public static function log(): static {
        return static::make('Log');
    }

    public static function queue(): static {
        return static::make('Queue');
    }

    public static function eloquent(): static {
        return static::make('Eloquent');
    }

    public static function dataLoader(): static {
        return static::make('DataLoader');
    }

    public static function logger(): static {
        return static::make('Logger');
    }
}
