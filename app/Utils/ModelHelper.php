<?php declare(strict_types = 1);

namespace App\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function class_uses_recursive;
use function in_array;
use function is_object;

class ModelHelper {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, bool>
     */
    private static array $softDeletable = [];

    /**
     * @param \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    public static function isSoftDeletable(Model|string $model): bool {
        if (is_object($model)) {
            $model = $model::class;
        }

        if (!isset(self::$softDeletable[$model::class])) {
            self::$softDeletable[$model::class] = in_array(SoftDeletes::class, class_uses_recursive($model), true);
        }

        return self::$softDeletable[$model::class];
    }
}
