<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;

class PaginatedTrashed extends BaseDirective implements ArgBuilderDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Adds `withTrashed()` to the builder.
            """
            directive @paginatedTrashed on ARGUMENT_DEFINITION
        GRAPHQL;
    }

    /**
     * @inheritdoc
     *
     * @return EloquentBuilder<Model>|QueryBuilder
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        if ($builder instanceof EloquentBuilder && $value !== null) {
            switch ($value) {
                case Trashed::include():
                    $builder = $builder->withTrashed();
                    break;
                case Trashed::only():
                    $builder = $builder->onlyTrashed();
                    break;
                default:
                    // empty
                    break;
            }
        }

        return $builder;
    }
}
