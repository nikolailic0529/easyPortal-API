<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

abstract class Root extends AuthDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Authenticated user must be a root.
            """
            directive @authRoot on FIELD_DEFINITION | OBJECT | ARGUMENT_DEFINITION
            GRAPHQL;
    }

    protected function isAuthorized(Authenticatable|null $user, mixed $root): bool {
        return $this->auth->isRoot($user);
    }
}
