<?php declare(strict_types = 1);

namespace App\Services\Audit\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Action extends Enum {
    public static function modelCreated(): static {
        return static::make('model.created');
    }

    public static function modelUpdated(): static {
        return static::make('model.updated');
    }

    public static function modelDeleted(): static {
        return static::make('model.deleted');
    }

    public static function authSignedIn(): static {
        return static::make('auth.signedIn');
    }

    public static function authSignedOut(): static {
        return static::make('auth.signedOut');
    }

    public static function exported(): static {
        return static::make('exported');
    }

    public static function authFailed(): static {
        return static::make('auth.failed');
    }
}
