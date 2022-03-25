<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\HasOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

abstract class Org extends AuthDirective implements FieldMiddleware {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        parent::__construct();
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Authenticated user must be a member of the current organization.
            """
            directive @authOrg on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(Authenticatable|null $user, mixed $root): bool {
        return $user instanceof HasOrganization
            && $this->organization->defined()
            && $this->organization->is($user->getOrganization());
    }
}
