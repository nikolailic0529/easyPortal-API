<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Invitation;
use App\Models\OrganizationUser;
use App\Models\Status;
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

    public function status(OrganizationUser $user): Status {
        // Statuses
        $statuses = [
            'active'   => (new Status())->forceFill([
                'id'   => 'f482da3b-f3e9-4af3-b2ab-8e4153fa8eb1',
                'key'  => 'active',
                'name' => 'active',
            ]),
            'inactive' => (new Status())->forceFill([
                'id'   => '347e5072-9cd8-42a7-a1be-47f329a9e3eb',
                'key'  => 'inactive',
                'name' => 'inactive',
            ]),
            'invited'  => (new Status())->forceFill([
                'id'   => '849deaf1-1ff4-4cd4-9c03-a1c4d9ba0402',
                'key'  => 'invited',
                'name' => 'invited',
            ]),
            'expired'  => (new Status())->forceFill([
                'id'   => 'c4136a8c-7cc4-4e30-8712-e47565a5e167',
                'key'  => 'expired',
                'name' => 'expired',
            ]),
        ];

        // Disabled
        if (!$user->enabled) {
            return $statuses['inactive'];
        }

        // Invited?
        if ($user->invited) {
            $last = Invitation::query()
                ->where('organization_id', '=', $user->organization_id)
                ->where('user_id', '=', $user->user_id)
                ->orderByDesc('created_at')
                ->first();

            if ($last && $last->expired_at->isFuture()) {
                return $statuses['invited'];
            } else {
                return $statuses['expired'];
            }
        }

        // Active
        return $statuses['active'];
    }
}
