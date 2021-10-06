<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Enums\UserType;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Builder;

class Users {
    public function __construct(
        protected Auth $auth,
        protected AuthManager $authManager,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function __invoke(Builder $builder): Builder {
        if (!$this->auth->isRoot($this->authManager->user())) {
            $builder = $builder->where('type', '=', UserType::keycloak());
        }

        return $builder;
    }
}
