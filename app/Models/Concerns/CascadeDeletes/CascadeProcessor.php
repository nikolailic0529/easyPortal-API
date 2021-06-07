<?php declare(strict_types = 1);

namespace App\Models\Concerns\CascadeDeletes;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

use function array_filter;
use function array_map;
use function is_a;
use function str_starts_with;

class CascadeProcessor {
    public function delete(Model $model): void {
        foreach ($this->getRelations($model) as $name => $relation) {
            $this->runDelete($model, $name, $relation);
        }
    }

    /**
     * @return array<\Illuminate\Database\Eloquent\Relations\Relation>
     */
    protected function getRelations(Model $model): array {
        $methods   = (new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC);
        $relations = [];

        foreach ($methods as $method) {
            $name     = $method->getName();
            $type     = $method->getReturnType();
            $relation = null;

            if ($type instanceof ReflectionNamedType && is_a($type->getName(), Relation::class, true)) {
                $relation = $model->{$name}();
            }

            if ($relation instanceof Relation && $this->isRelation($model, $name, $relation)) {
                $relations[$name] = $relation;
            }
        }

        return $relations;
    }

    protected function isRelation(Model $model, string $name, Relation $relation): bool {
        $cascade = false;

        if ($relation instanceof MorphMany || $this->isBelongsToMany($model, $name, $relation)) {
            $cascade = true;
        } else {
            // When: items - item_types
            $base    = Str::singular($model->getTable()).'_';
            $table   = $relation->newModelInstance()->getTable();
            $cascade = str_starts_with($table, $base);
        }

        if ($model instanceof CascadeDeletable) {
            $cascade = $model->isCascadeDeletableRelation($name, $relation, $cascade);
        }

        return $cascade;
    }

    protected function runDelete(Model $model, string $name, Relation $relation): void {
        foreach ($this->getRelatedObjects($model, $name, $relation) as $object) {
            if ($object instanceof Model) {
                $object->forceDeleting = $model->forceDeleting ?? false;

                if (!$object->delete()) {
                    throw new Exception('Unknown error while deleting children.');
                }
            }
        }
    }

    /**
     * @return array<\Illuminate\Database\Eloquent\Model>
     */
    protected function getRelatedObjects(Model $model, string $name, Relation $relation): array {
        $value  = $model->getRelationValue($name);
        $values = [];

        if ($value instanceof Collection) {
            $values = $value->all();
        } elseif ($value instanceof Model) {
            $values = [$value];
        } else {
            // empty
        }

        if ($values && $this->isBelongsToMany($model, $name, $relation)) {
            $accessor = $relation->getPivotAccessor();
            $values   = array_filter(array_map(static function (Model $model) use ($accessor): ?Model {
                return $model->{$accessor};
            }, $values));
        }

        return $values;
    }

    protected function isBelongsToMany(Model $model, string $name, Relation $relation): bool {
        return $relation instanceof BelongsToMany && (!$relation instanceof MorphToMany);
    }
}
