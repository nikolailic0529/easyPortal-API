<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Directives\AuthDirective;
use App\Services\Tenant\Tenant;
use App\Services\Tenant\Tenantable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

abstract class Organization extends AuthDirective implements FieldMiddleware {
    public function __construct(
        Factory $auth,
        Repository $config,
        protected Tenant $tenant,
    ) {
        parent::__construct($auth, $config);
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Marks that organization required and the current user should be a
            member of it.
            """
            directive @organization on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(?Authenticatable $user): bool {
        return $user instanceof Tenantable
            && $this->tenant->has()
            && $this->tenant->is($user->getOrganization());
    }
}
