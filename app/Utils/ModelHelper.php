<?php declare(strict_types = 1);

namespace App\Utils;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LastDragon_ru\LaraASP\Eloquent\ModelHelper as LaraAspModelHelper;

use function class_uses_recursive;
use function in_array;
use function is_string;

class ModelHelper extends LaraAspModelHelper {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, bool>
     */
    private static array $softDeletable = [];

    /**
     * @param \Illuminate\Database\Eloquent\Builder
     *      |\Illuminate\Database\Eloquent\Model
     *      |class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    public function __construct(Builder|Model|string $model) {
        if (is_string($model)) {
            $model = new $model();
        }

        parent::__construct($model);
    }

    public function isSoftDeletable(): bool {
        $model = $this->getModel()::class;

        if (!isset(self::$softDeletable[$model])) {
            self::$softDeletable[$model] = in_array(SoftDeletes::class, class_uses_recursive($model), true);
        }

        return self::$softDeletable[$model];
    }
}
