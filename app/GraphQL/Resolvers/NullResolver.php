<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

class NullResolver {
    public function __invoke(): mixed {
        return null;
    }
}
