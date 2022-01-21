<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Enums\UserOrganizationStatus;
use App\Models\Invitation;
use App\Models\OrganizationUser;
use App\Models\User as UserModel;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Collection;

class User {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function invitations(UserModel $user): Collection {
        return $user->invitations()
            ->where('organization_id', '=', $this->organization->getKey())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function status(OrganizationUser $user): UserOrganizationStatus {
        // Disabled
        if (!$user->enabled) {
            return UserOrganizationStatus::inactive();
        }

        // Invited?
        if ($user->invited) {
            $last = Invitation::query()
                ->where('organization_id', '=', $user->organization_id)
                ->where('user_id', '=', $user->user_id)
                ->orderByDesc('created_at')
                ->first();

            if ($last && $last->expired_at->isFuture()) {
                return UserOrganizationStatus::invited();
            } else {
                return UserOrganizationStatus::expired();
            }
        }

        // Active
        return UserOrganizationStatus::active();
    }
}
