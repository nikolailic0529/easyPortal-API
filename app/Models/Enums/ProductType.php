<?php declare(strict_types = 1);

namespace App\Models\Enums;

class ProductType extends Enum {
    public static function asset(): static {
        return static::create(__FUNCTION__);
    }

    public static function service(): static {
        return static::create(__FUNCTION__);
    }
}
