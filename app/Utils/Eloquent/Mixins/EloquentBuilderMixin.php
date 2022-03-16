<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Mixins;

use App\Utils\Eloquent\ModelHelper;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

use function sprintf;

class EloquentBuilderMixin {
    public function whereMatchAgainst(): Closure {
        return function (string $property, mixed $value): Builder {
            /** @var Builder $this */
            return $this->whereRaw("MATCH({$property}) AGAINST (?)", [$value]);
        };
    }

    public function joinRelation(): Closure {
        return function (string $relation, string $alias = null, Closure $callback = null): Builder {
            /** @var Builder $this */
            $builder  = $this;
            $relation = (new ModelHelper($builder))->getRelation($relation);

            if (!$alias) {
                $alias = $relation->getRelationCountHash();
            }

            if ($relation instanceof BelongsToMany) {
                $builder = $builder->joinSub(
                    $callback
                        ? $callback($relation, $relation->getQuery())
                        : $relation->getQuery(),
                    $alias,
                    "{$alias}.{$relation->getForeignPivotKeyName()}",
                    '=',
                    $relation->getQualifiedParentKeyName(),
                );
            } elseif ($relation instanceof HasMany) {
                $builder = $builder->joinSub(
                    $callback
                        ? $callback($relation, $relation->getQuery())
                        : $relation->getQuery(),
                    $alias,
                    "{$alias}.{$relation->getForeignKeyName()}",
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
