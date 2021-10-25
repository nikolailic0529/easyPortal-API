<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators\Comparison;

use App\GraphQL\Directives\SearchBy\Metadata;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @mixin \LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Contains
 * @mixin \LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\StartsWith
 * @mixin \LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\EndsWith
 */
trait Fulltext {
    public function __construct(
        protected Metadata $metadata,
    ) {
        parent::__construct();
    }

    public function apply(
        EloquentBuilder|QueryBuilder $builder,
        string $property,
        mixed $value,
    ): EloquentBuilder|QueryBuilder {
        if ($this->match($builder, $property)) {
            $builder = $builder->where(function (EloquentBuilder|QueryBuilder $builder) use ($property, $value): void {
                parent::apply($builder, $property, $value)->whereMatchAgainst($property, $value);
            });
        } else {
            $builder = parent::apply($builder, $property, $value);
        }

        return $builder;
    }

    protected function match(EloquentBuilder|QueryBuilder $builder, string $property): bool {
        return $this->metadata->isFulltextIndexExists($builder, $property);
    }
}
