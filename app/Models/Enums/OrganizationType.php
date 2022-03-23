<?php declare(strict_types = 1);

namespace App\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class OrganizationType extends Enum {
    public static function reseller(): static {
        return static::make(__FUNCTION__);
    }
}
