<?php declare(strict_types = 1);

namespace App\Mixins;

use App\Utils\ModelHelper;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

use function sprintf;

class EloquentBuilderMixin {
    public function joinRelation(): Closure {
        return function (string $relation, string $alias = null, Closure $callback = null): Builder {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            $builder  = $this;
            $relation = (new ModelHelper($builder))->getRelation($relation);

            if (!$alias) {
                $alias = $relation->getRelationCountHash();
            }

            if ($relation instanceof BelongsToMany) {
                $builder = $builder->leftJoinSub(
                    $callback
                        ? $callback($relation, $relation->getQuery())
                        : $relation->getQuery(),
                    $alias,
                    "{$alias}.{$relation->getForeignPivotKeyName()}",
                    '=',
                    $relation->getQualifiedParentKeyName(),
                );
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Relation `%s` not supported',
                    $relation::class,
                ));
            }

            return $builder;
        };
    }
}
