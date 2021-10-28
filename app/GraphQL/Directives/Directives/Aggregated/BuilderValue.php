<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\Services\Search\Builders\Builder as SearchBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use RuntimeException;

use function sprintf;

class BuilderValue {
    public function __construct(
        protected QueryBuilder|EloquentBuilder|SearchBuilder $builder,
    ) {
        // empty
    }

    public function getBuilder(): EloquentBuilder|SearchBuilder|QueryBuilder {
        return clone $this->builder;
    }

    public function getEloquentBuilder(): EloquentBuilder {
        if (!($this->builder instanceof EloquentBuilder)) {
            throw new RuntimeException(sprintf(
                'Builder is instance of `%s` instead of `%s`.',
                $this->builder::class,
                EloquentBuilder::class,
            ));
        }

        return clone $this->builder;
    }
}
