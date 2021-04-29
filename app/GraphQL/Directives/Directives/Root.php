<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Directives\AuthDirective;
use Illuminate\Contracts\Auth\Authenticatable;

use function in_array;

abstract class Root extends AuthDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Checks that current user is the root.
            """
            directive @root(
                """
                Specify which guards to use, e.g. ["api"].
                When not defined, the default from `lighthouse.php` is used.
                """
                with: [String!]
            ) on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(Authenticatable|null $user): bool {
        return $this->isRoot($user);
    }

    public function isRoot(Authenticatable|null $user): bool {
        return $user
            && in_array($user->getAuthIdentifier(), (array) $this->config->get('ep.root_users'), true);
    }
}
