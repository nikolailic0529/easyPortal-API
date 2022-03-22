<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

/**
 * This will return an empty resolver to be used in case of
 * applying an extra layer with no resolver
 */
class EmptyResolver {
    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $root, array $args): bool {
        return false;
    }
}
