<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Directives\AuthDirective;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

abstract class Guest extends AuthDirective implements FieldMiddleware {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Checks that current visitor is guest.
            """
            directive @guest(
                """
                Specify which guards to use, e.g. ["api"].
                When not defined, the default from `lighthouse.php` is used.
                """
                with: [String!]
            ) on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthenticated(Guard $guard): bool {
        return !parent::isAuthenticated($guard);
    }

    protected function isAuthorized(?Authenticatable $user): bool {
        if ($user) {
            throw new AuthenticationException(
                AuthenticationException::MESSAGE,
                $this->getGuards(),
            );
        }

        return true;
    }
}
