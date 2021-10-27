<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\Services\Search\Builders\Builder as SearchBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class BuilderValue {
    public function __construct(
        protected QueryBuilder|EloquentBuilder|SearchBuilder $builder,
    ) {
        // empty
    }

    public function getBuilder(): EloquentBuilder|SearchBuilder|QueryBuilder {
        return $this->builder;
    }
}
