<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Complex;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Eloquent\Enum;
use LastDragon_ru\LaraASP\Eloquent\ModelHelper;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Complex\Relation as SearchByRelation;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

use function array_map;
use function array_values;
use function is_a;
use function sprintf;

class Relation extends SearchByRelation {
    protected function build(
        EloquentBuilder $builder,
        Property $property,
        string $operator,
        int $count,
        Closure $closure,
    ): void {
        $name     = (string) $property;
        $relation = (new ModelHelper($builder))->getRelation($name);

        if ($relation instanceof HasManyDeep) {
            parent::build($builder, $property, $operator, $count, $closure);
        } elseif ($relation instanceof MorphTo) {
            $types = $this->getMorphTypes($builder, $name, $relation);

            $builder->hasMorphIn($name, $types, $operator, $count, 'and', $closure);
        } else {
            $builder->whereHasIn($name, $closure, $operator, $count);
        }
    }

    /**
     * @param EloquentBuilder<Model> $builder
     * @param MorphTo<Model, Model>  $relation
     *
     * @return array<string>
     */
    protected function getMorphTypes(EloquentBuilder $builder, string $property, MorphTo $relation): array {
        $cast   = $builder->getModel()->getCasts()[$relation->getMorphType()] ?? null;
        $values = is_a($cast, Enum::class, true)
            ? array_values(array_map('strval', $cast::getValues()))
            : null;

        if (!$values) {
            throw new InvalidArgumentException(sprintf(
                'Impossible to determine MorphTo types for `%s::$%s`',
                $builder->getModel()::class,
                $property,
            ));
        }

        return $values;
    }
}
