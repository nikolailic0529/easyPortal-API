<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\GraphQL\Directives\Directives\Cached\ParentValue;
use App\Services\Search\Builders\Builder as SearchBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use RuntimeException;

use function sprintf;

/**
 * @template TModel of Model
 */
class BuilderValue extends ParentValue {
    /**
     * @inheritDoc
     *
     * @param EloquentBuilder<TModel>|QueryBuilder|SearchBuilder<TModel> $builder
     */
    public function __construct(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
        protected EloquentBuilder|QueryBuilder|SearchBuilder $builder,
    ) {
        parent::__construct($root, $args, $context, $resolveInfo);
    }

    /**
     * @return EloquentBuilder<TModel>|SearchBuilder<TModel>|QueryBuilder
     */
    public function getBuilder(): EloquentBuilder|SearchBuilder|QueryBuilder {
        return clone $this->builder;
    }

    /**
     * @return EloquentBuilder<TModel>
     */
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
