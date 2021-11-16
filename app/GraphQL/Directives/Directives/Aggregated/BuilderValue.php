<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\GraphQL\Directives\Directives\Cached\Root;
use App\Services\Search\Builders\Builder as SearchBuilder;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use RuntimeException;

use function sprintf;

class BuilderValue extends Root {
    /**
     * @inheritDoc
     */
    public function __construct(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
        protected QueryBuilder|EloquentBuilder|SearchBuilder $builder,
    ) {
        parent::__construct($root, $args, $context, $resolveInfo);
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
