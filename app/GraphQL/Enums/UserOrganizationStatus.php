<?php declare(strict_types = 1);

namespace App\GraphQL\Enums;

use LastDragon_ru\LaraASP\Core\Enum;

class UserOrganizationStatus extends Enum {
    public static function active(): static {
        return static::make(__FUNCTION__);
    }

    public static function invited(): static {
        return static::make(__FUNCTION__);
    }

    public static function expired(): static {
        return static::make(__FUNCTION__);
    }

    public static function inactive(): static {
        return static::make(__FUNCTION__);
    }
}
