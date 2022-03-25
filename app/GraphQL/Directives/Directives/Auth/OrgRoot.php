<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

abstract class OrgRoot extends Org {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Authenticated user must be a member of the current organization, and
            the organization must be a root organization.
            """
            directive @authOrgRoot on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(Authenticatable|null $user, mixed $root): bool {
        return parent::isAuthorized($user, $root)
            && $this->organization->isRoot();
    }
}
