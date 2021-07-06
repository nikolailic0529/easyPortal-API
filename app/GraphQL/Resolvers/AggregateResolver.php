<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

abstract class AggregateResolver {
    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): mixed {
        $query  = $this->getQuery();
        $result = $resolveInfo->argumentSet->enhanceBuilder($query, []);
        $result = $this->getResult($result);

        return $result;
    }

    protected function getResult(DatabaseBuilder|EloquentBuilder $builder): mixed {
        return $builder->first();
    }

    abstract protected function getQuery(): DatabaseBuilder|EloquentBuilder;
}
