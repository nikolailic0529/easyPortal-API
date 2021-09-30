<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Enums\UserType;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\Team;
use App\Models\User as UserModel;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class User {
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

    public function role(UserModel $user): ?Role {
        $organizationUser = OrganizationUser::query()
            ->where('organization_id', '=', $this->organization->getKey())
            ->where('user_id', '=', $user->getKey())
            ->first();

        return $organizationUser?->role;
    }

    public function team(UserModel $user): ?Team {
        $organizationUser = OrganizationUser::query()
            ->where('organization_id', '=', $this->organization->getKey())
            ->where('user_id', '=', $user->getKey())
            ->first();

        return $organizationUser?->team;
    }

    public function invitations(UserModel $user): Collection {
        return $user->invitations()
            ->where('organization_id', '=', $this->organization->getKey())
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
