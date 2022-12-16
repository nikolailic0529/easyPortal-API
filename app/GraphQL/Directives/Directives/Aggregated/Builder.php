<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\Services\Search\Builders\Builder as SearchBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder {
    /**
     * @template TModel of Model
     *
     * @param BuilderValue<TModel> $value
     *
     * @return EloquentBuilder<TModel>|SearchBuilder<TModel>|QueryBuilder
     */
    public function __invoke(BuilderValue $value): EloquentBuilder|SearchBuilder|QueryBuilder {
        return $value->getBuilder();
    }
}
