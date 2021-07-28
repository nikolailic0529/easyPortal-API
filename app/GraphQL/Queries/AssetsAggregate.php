<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AssetsAggregate {
    public function __construct(
        protected AssetsAggregateCount $assetsAggregateCount,
        protected AssetsAggregateTypes $assetsAggregateType,
        protected AssetsAggregateCoverages $assetsAggregateCoverages,
    ) {
        //empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<string,mixed>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array {
        return [
            'count'     => ($this->assetsAggregateCount)($root, $args, $context, $resolveInfo),
            'types'     => ($this->assetsAggregateType)($root, $args, $context, $resolveInfo),
            'coverages' => ($this->assetsAggregateCoverages)($root, $args, $context, $resolveInfo),
        ];
    }
}
