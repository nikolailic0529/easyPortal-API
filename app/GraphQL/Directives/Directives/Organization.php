<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Directives\AuthDirective;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\HasOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

abstract class Organization extends AuthDirective implements FieldMiddleware {
    public function __construct(
        Factory $auth,
        Repository $config,
        protected CurrentOrganization $organization,
    ) {
        parent::__construct($auth, $config);
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Current user must be a member of the current organization.
            """
            directive @organization on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(?Authenticatable $user): bool {
        return $user instanceof HasOrganization
            && $this->organization->defined()
            && $this->organization->is($user->getOrganization());
    }
}
