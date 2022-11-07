<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use Illuminate\Contracts\Validation\Validator;
use WeakMap;

final class QueryOperationCache {
    /**
     * @var WeakMap<Validator, QueryOperation>
     */
    protected static WeakMap $cache;

    public static function get(Validator $validator): ?QueryOperation {
        return self::$cache[$validator] ?? null;
    }

    public static function set(Validator $validator, QueryOperation $operation): void {
        if (!isset(self::$cache)) {
            self::$cache = new WeakMap();
        }

        self::$cache[$validator] = $operation;
    }
}
