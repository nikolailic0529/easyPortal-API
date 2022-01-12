<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\Team;
use App\Models\User as UserModel;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Collection;

class User {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @deprecated
     */
    public function role(UserModel $user): ?Role {
        $organizationUser = OrganizationUser::query()
            ->where('organization_id', '=', $this->organization->getKey())
            ->where('user_id', '=', $user->getKey())
            ->first();

        return $organizationUser?->role;
    }

    /**
     * @deprecated
     */
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
