<?php declare(strict_types = 1);

namespace App\Services\Audit\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Action extends Enum {
    public static function created(): static {
        return static::make('model.created');
    }

    public static function updated(): static {
        return static::make('model.updated');
    }

    public static function deleted(): static {
        return static::make('model.deleted');
    }

    public static function signedIn(): static {
        return static::make('auth.signedIn');
    }

    public static function signedOut(): static {
        return static::make('auth.signedOut');
    }
}
