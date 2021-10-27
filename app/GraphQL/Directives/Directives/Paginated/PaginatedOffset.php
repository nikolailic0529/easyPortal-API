<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\Services\Search\Builders\Builder as SearchBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use Laravel\Scout\Builder as ScoutBuilder;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Scout\ScoutBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;

use function sprintf;

class PaginatedOffset extends BaseDirective implements ArgBuilderDirective, ScoutBuilderDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Adds offset clause to the builder.
            """
            directive @paginatedOffset on ARGUMENT_DEFINITION
        GRAPHQL;
    }

    /**
     * @inheritdoc
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        if ($value !== null) {
            $builder = $builder->offset((int) $value);
        }

        return $builder;
    }

    public function handleScoutBuilder(ScoutBuilder $builder, mixed $value): ScoutBuilder {
        if (!($builder instanceof SearchBuilder)) {
            throw new InvalidArgumentException(sprintf(
                'The `$builder` must be instance of `%s`, `%s` given.',
                SearchBuilder::class,
                $builder::class,
            ));
        }

        if ($value !== null) {
            $builder = $builder->offset((int) $value);
        }

        return $builder;
    }
}
