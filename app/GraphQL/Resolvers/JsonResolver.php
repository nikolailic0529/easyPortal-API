<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function is_array;
use function is_object;
use function json_encode;

/**
 * Convert value into JSON.
 */
class JsonResolver {
    /**
     * @param array<mixed> $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): ?string {
        $value = null;

        if (is_object($root)) {
            $value = $root->{$info->fieldName} ?? null;
        } elseif (is_array($root)) {
            $value = $root[$info->fieldName] ?? null;
        } else {
            // empty
        }

        if ($value !== null) {
            $value = json_encode($value);
        }

        return $value;
    }
}
