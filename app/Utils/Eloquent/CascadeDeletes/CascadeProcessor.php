<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\CascadeDeletes;

use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use LogicException;
use ReflectionClass;
use ReflectionMethod;

use function array_filter;
use function array_map;
use function property_exists;
use function sprintf;

class CascadeProcessor {
    public function delete(Model $model): bool {
        if ($model instanceof DataModel) {
            throw new LogicException(sprintf(
                'DataModel `%s` cannot be deleted.',
                $model::class,
            ));
        }

        foreach ($this->getRelations($model) as $name => $relation) {
            $this->run($model, $name, $relation);
        }

        return true;
    }

    /**
     * @return array<Relation<Model>>
     */
    protected function getRelations(Model $model): array {
        $helper    = new ModelHelper($model);
        $methods   = (new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC);
        $relations = [];
        $ignored   = [
            'hasManyDeepFromRelationsWithConstraints' => true,
            'hasOneDeepFromRelationsWithConstraints'  => true,
            'hasManyDeepFromReverse'                  => true,
            'hasOneDeepFromReverse'                   => true,
        ];

        foreach ($methods as $method) {
            // Relation?
            $name = $method->getName();

            if (isset($ignored[$name]) || !$helper->isRelation($name)) {
                continue;
            }

            // Attribute?
            if ($method->getAttributes(CascadeDelete::class)) {
                $relations[$name] = $helper->getRelation($name);
            }
        }

        return $relations;
    }

    /**
     * @param Relation<Model> $relation
     */
    protected function run(Model $model, string $name, Relation $relation): void {
        GlobalScopes::callWithoutAll(function () use ($model, $name, $relation): void {
            foreach ($this->getRelatedObjects($model, $name, $relation) as $object) {
                if (property_exists($object, 'forceDeleting')) {
                    $object->forceDeleting = property_exists($model, 'forceDeleting')
                        ? $model->forceDeleting
                        : false;
                }

                if (!$object->delete()) {
                    throw new LogicException('Unknown error while deleting children.');
                }
            }
        });
    }

    /**
     * @param Relation<Model> $relation
     *
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

        if ($values && $relation instanceof BelongsToMany && $this->isBelongsToMany($model, $name, $relation)) {
            $accessor = $relation->getPivotAccessor();
            $values   = array_filter(array_map(static function (Model $model) use ($accessor): ?Model {
                return $model->getAttribute($accessor);
            }, $values));
        }

        return $values;
    }

    /**
     * @param Relation<Model> $relation
     */
    protected function isBelongsToMany(Model $model, string $name, Relation $relation): bool {
        return $relation instanceof BelongsToMany && (!$relation instanceof MorphToMany);
    }
}
