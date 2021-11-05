<?php declare(strict_types = 1);

namespace App\Utils;

use Illuminate\Database\Eloquent\SoftDeletes;
use LastDragon_ru\LaraASP\Eloquent\ModelHelper as LaraAspModelHelper;

use function class_uses_recursive;
use function in_array;

class ModelHelper extends LaraAspModelHelper {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, bool>
     */
    private static array $softDeletable = [];

    public function isSoftDeletable(): bool {
        $model = $this->getModel()::class;

        if (!isset(self::$softDeletable[$model])) {
            self::$softDeletable[$model] = in_array(SoftDeletes::class, class_uses_recursive($model), true);
        }

        return self::$softDeletable[$model];
    }
}
