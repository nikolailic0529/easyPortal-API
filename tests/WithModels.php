<?php declare(strict_types = 1);

namespace Tests;

use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function method_exists;

trait WithModels {
    /**
     * @param array<class-string<Model>,int> $expected
     */
    protected static function assertModelsCount(array $expected): void {
        $actual = [];

        foreach ($expected as $model => $count) {
            $actual[$model] = GlobalScopes::callWithoutAll(static function () use ($model): int {
                return $model::query()->count();
            });
        }

        self::assertEquals($expected, $actual);
    }

    /**
     * @param array<array-key, bool>                    $expected
     * @param Collection<array-key, Model>|array<Model> $models
     */
    protected static function assertModelsTrashed(array $expected, Collection|array $models): void {
        $actual = (new Collection($models))
            ->map(static function (mixed $model): bool {
                return $model instanceof Model
                    && self::withModelsIsTrashed($model->fresh());
            })
            ->all();

        self::assertEquals($expected, $actual);
    }

    private static function withModelsIsTrashed(?Model $model): bool {
        return $model && method_exists($model, 'trashed') ? $model->trashed() : false;
    }
}
