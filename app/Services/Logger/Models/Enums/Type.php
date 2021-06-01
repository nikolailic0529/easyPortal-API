<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Type extends Enum {
    public static function job(): static {
        return static::make(__FUNCTION__);
    }
}
