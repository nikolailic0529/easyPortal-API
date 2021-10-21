<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\GraphQL\Queries\Assets\AssetsAggregate;
use App\GraphQL\Queries\Contracts\ContractsAggregate;
use App\GraphQL\Resolvers\LazyValue;
use App\Models\Customer as CustomerModel;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Customer {
    public function __construct(
        protected AssetsAggregate $assetsAggregate,
        protected ContractsAggregate $contractsAggregate,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     */
    public function assetsAggregate(
        CustomerModel $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): LazyValue {
        return ($this->assetsAggregate)($root, $args, $context, $resolveInfo);
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<mixed>
     */
    public function contractsAggregate(
        CustomerModel $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): array {
        return ($this->contractsAggregate)($root, $args, $context, $resolveInfo);
    }
}
