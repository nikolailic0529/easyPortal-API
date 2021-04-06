<?php declare(strict_types = 1);

namespace App;

use LastDragon_ru\LaraASP\Core\Enum;

class Disc extends Enum {
    public static function app(): static {
        return static::get('app');
    }
}
