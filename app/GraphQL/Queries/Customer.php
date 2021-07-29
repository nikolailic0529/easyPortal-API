<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer as CustomerModel;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Customer {
    public function __construct(
        protected AssetsAggregate $assetsAggregate,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function assetsAggregate(
        CustomerModel $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): array {
        return ($this->assetsAggregate)($root, $args, $context, $resolveInfo);
    }
}
