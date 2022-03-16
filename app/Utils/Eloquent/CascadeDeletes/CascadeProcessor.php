<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\CascadeDeletes;

use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\ModelHelper;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

use function array_filter;
use function array_map;
use function reset;
use function sprintf;

class CascadeProcessor {
    public function delete(Model $model): void {
        foreach ($this->getRelations($model) as $name => $relation) {
            $this->runDelete($model, $name, $relation);
        }
    }

    /**
     * @return array<Relation>
     */
    protected function getRelations(Model $model): array {
        $helper    = new ModelHelper($model);
        $methods   = (new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC);
        $relations = [];

        foreach ($methods as $method) {
            // Relation?
            $name = $method->getName();

            if (!$helper->isRelation($name)) {
                continue;
            }

            // Attribute?
            $attribute = $this->getAttribute($method);

            if ($attribute) {
                if ($attribute->isDelete()) {
                    $relations[$name] = $helper->getRelation($name);
                }
            } else {
                throw new Exception(sprintf(
                    'Relation `%s::%s()` must have `%s` attribute.',
                    $model::class,
                    $name,
                    CascadeDelete::class,
                ));
            }
        }

        return $relations;
    }

    protected function getAttribute(ReflectionMethod $method): ?CascadeDelete {
        $attributes = $method->getAttributes(CascadeDelete::class);
        $attribute  = reset($attributes) ?: null;
        $attribute  = $attribute?->newInstance();

        return $attribute;
    }

    protected function runDelete(Model $model, string $name, Relation $relation): void {
        GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            function () use ($model, $name, $relation): void {
                foreach ($this->getRelatedObjects($model, $name, $relation) as $object) {
                    if ($object instanceof Model) {
                        $object->forceDeleting = $model->forceDeleting ?? false;

                        if (!$object->delete()) {
                            throw new Exception('Unknown error while deleting children.');
                        }
                    }
                }
            },
        );
    }

    /**
     * @return array<Model>
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
