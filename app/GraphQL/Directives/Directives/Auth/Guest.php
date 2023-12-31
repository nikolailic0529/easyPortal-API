<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

use function is_null;

abstract class Guest extends AuthDirective implements FieldMiddleware {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Current visitor must be a guest.
            """
            directive @authGuest on FIELD_DEFINITION | OBJECT | ARGUMENT_DEFINITION
            GRAPHQL;
    }

    protected function isAuthenticated(Authenticatable|null $user): bool {
        return is_null($user);
    }

    protected function isAuthorized(Authenticatable|null $user, mixed $root): bool {
        return is_null($user);
    }
}
