<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

use Illuminate\Database\Eloquent\Model;

use function is_string;

class TypeNameResolver {
    public function __invoke(mixed $root): ?string {
        return match (true) {
            $root instanceof Model    => $root->getMorphClass(),
            is_string($root) && $root => $root,
            default                   => null,
        };
    }
}
