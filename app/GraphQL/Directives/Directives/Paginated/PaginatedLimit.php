<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Scout\ScoutBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;

class PaginatedLimit extends BaseDirective implements ArgBuilderDirective, ScoutBuilderDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Adds limit clause to the builder.
            """
            directive @paginatedLimit on ARGUMENT_DEFINITION
        GRAPHQL;
    }

    /**
     * @inheritdoc
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        if ($value !== null) {
            $builder = $builder->limit((int) $value);
        }

        return $builder;
    }

    public function handleScoutBuilder(ScoutBuilder $builder, mixed $value): ScoutBuilder {
        if ($value !== null) {
            $builder = $builder->take((int) $value);
        }

        return $builder;
    }
}
