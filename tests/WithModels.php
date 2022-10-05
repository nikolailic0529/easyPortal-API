<?php declare(strict_types = 1);

namespace Tests;

use App\Utils\Cast;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

use function array_filter;
use function array_keys;
use function array_merge;
use function assert;
use function implode;
use function method_exists;
use function sprintf;

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

    protected static function assertModelHasAllRelations(Model $model): void {
        $relations = self::withModelsGetRelations($model);
        $empty     = array_filter($relations, static function (mixed $value): bool {
            return $value === null;
        });

        self::assertEmpty($empty, sprintf(
            'Following relations are empty: `%s`',
            implode('`, `', array_keys($empty)),
        ));
    }

    /**
     * @param array<string, bool> $stack
     *
     * @return array<string, bool|null>
     */
    private static function withModelsGetRelations(Model $model, string $base = null, array &$stack = []): array {
        // Processed?
        $key = Cast::toScalar($model->getKey());
        $key = "{$model->getMorphClass()}@{$key}";

        if (isset($stack[$key]) || $model instanceof DataModel) {
            return [];
        }

        // Process
        $class     = new ReflectionClass($model);
        $stack     = array_merge($stack, [$key => true]);
        $helper    = new ModelHelper($model);
        $ignored   = [
            'hasManyDeepFromRelationsWithConstraints' => true,
            'hasOneDeepFromRelationsWithConstraints'  => true,
            'hasManyDeepFromReverse'                  => true,
            'hasOneDeepFromReverse'                   => true,
        ];
        $relations = [];

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Ignored?
            $name = $method->getName();

            if (isset($ignored[$name]) || !$helper->isRelation($name)) {
                continue;
            }

            // Relation?
            $relation = GlobalScopes::callWithoutAll(static function () use ($helper, $name): Relation {
                return $helper->getRelation($name);
            });

            if ($relation instanceof HasManyThrough) {
                continue;
            }

            // Check value
            $path  = $base ? "{$base}.{$name}" : $name;
            $value = GlobalScopes::callWithoutAll(static function () use ($relation): mixed {
                return $relation->getResults();
            });

            if ($value instanceof Collection && !$value->isEmpty()) {
                foreach ($value as $item) {
                    assert($item instanceof Model);

                    $key       = Cast::toScalar($model->getKey());
                    $path      = "{$path}.{$key}";
                    $relations = array_merge(
                        $relations,
                        [
                            $path => self::withModelsIsTrashed($item),
                        ],
                        self::withModelsGetRelations($item, $path, $stack),
                    );
                }
            } elseif ($value instanceof Model) {
                $relations = array_merge(
                    $relations,
                    [
                        $path => self::withModelsIsTrashed($value),
                    ],
                    self::withModelsGetRelations($value, $path, $stack),
                );
            } else {
                $relations[$path] = null;
            }
        }

        return $relations;
    }

    private static function withModelsIsTrashed(?Model $model): bool {
        return $model && method_exists($model, 'trashed') ? $model->trashed() : false;
    }
}
