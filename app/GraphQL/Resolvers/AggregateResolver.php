<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Query\Builder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use stdClass;

abstract class AggregateResolver {
    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): stdClass {
        $result = $resolveInfo->argumentSet->enhanceBuilder($this->getQuery(), [])->first();

        return $result;
    }

    abstract protected function getQuery(): Builder;
}
