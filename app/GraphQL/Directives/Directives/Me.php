<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Directives\AuthDirective;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class Me extends AuthDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            User must be authenticated.
            """
            directive @me on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    public function isAuthorized(?Authenticatable $user): bool {
        return true;
    }
}
