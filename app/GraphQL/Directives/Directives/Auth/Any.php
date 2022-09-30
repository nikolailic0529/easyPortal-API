<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

abstract class Any extends AuthDirective implements FieldMiddleware {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Everyone/Everything allowed.
            """
            directive @authAny on FIELD_DEFINITION | OBJECT | ARGUMENT_DEFINITION
            GRAPHQL;
    }

    protected function isAuthenticated(Authenticatable|null $user): bool {
        return true;
    }

    protected function isAuthorized(Authenticatable|null $user, mixed $root): bool {
        return true;
    }
}
