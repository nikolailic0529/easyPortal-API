<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Map {
    public function __construct() {
        // empty
    }

    /**
     * @param array{static, mixed} $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): MapBuilder {
        return new MapBuilder($root, $args, $context, $resolveInfo);
    }
}
