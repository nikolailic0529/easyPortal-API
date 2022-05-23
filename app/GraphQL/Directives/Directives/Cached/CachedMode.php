<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use LastDragon_ru\LaraASP\Core\Enum;

class CachedMode extends Enum {
    /**
     * In this mode all queries will be cached always.
     */
    public static function normal(): static {
        return static::make(__FUNCTION__);
    }

    /**
     * In this mode, the query will be cached only if the execution time is
     * greater than the threshold.
     *
     * @see \Config\Constants::EP_CACHE_GRAPHQL_THRESHOLD
     */
    public static function threshold(): static {
        return static::make(__FUNCTION__);
    }
}
