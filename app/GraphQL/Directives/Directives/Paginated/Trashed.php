<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use LastDragon_ru\LaraASP\Core\Enum;

class Trashed extends Enum {
    /**
     * Trashed items will be returned too.
     */
    public static function include(): static {
        return static::make(__FUNCTION__);
    }

    /**
     * Only trashed items will be returned.
     */
    public static function only(): static {
        return static::make(__FUNCTION__);
    }
}
