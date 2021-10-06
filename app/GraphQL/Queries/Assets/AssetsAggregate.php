<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\GraphQL\Resolvers\LazyValue;
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
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): LazyValue {
        return new LazyValue([
            'count'     => function () use ($root, $args, $context, $resolveInfo): mixed {
                return ($this->assetsAggregateCount)($root, $args, $context, $resolveInfo);
            },
            'types'     => function () use ($root, $args, $context, $resolveInfo): mixed {
                return ($this->assetsAggregateType)($root, $args, $context, $resolveInfo);
            },
            'coverages' => function () use ($root, $args, $context, $resolveInfo): mixed {
                return ($this->assetsAggregateCoverages)($root, $args, $context, $resolveInfo);
            },
        ]);
    }
}
