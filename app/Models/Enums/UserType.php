<?php declare(strict_types = 1);

namespace App\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class UserType extends Enum {
    public static function local(): static {
        return static::make(__FUNCTION__);
    }

    public static function keycloak(): static {
        return static::make(__FUNCTION__);
    }
}
