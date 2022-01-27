<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Complex;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\Eloquent\ModelHelper;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Complex\Relation as SearchByRelation;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

class Relation extends SearchByRelation {
    protected function build(
        EloquentBuilder $builder,
        string $property,
        string $operator,
        int $count,
        Closure $closure,
    ): EloquentBuilder {
        $relation = (new ModelHelper($builder))->getRelation($property);

        return $relation instanceof HasManyDeep
            ? parent::build($builder, $property, $operator, $count, $closure)
            : $builder->whereHasIn($property, $closure, $operator, $count);
    }
}
