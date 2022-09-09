<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Data\Status;
use App\Models\Invitation;
use App\Models\OrganizationUser;
use App\Models\User as UserModel;
use App\Services\Organization\CurrentOrganization;

use function array_merge;

class User {
    /**
     * If you change this, please also update lang(s).
     *
     * @todo move to database
     *
     * @var array<string, array{id: string, key: string, name: string}>
     */
    protected static array $statuses = [
        'active'   => [
            'id'   => 'f482da3b-f3e9-4af3-b2ab-8e4153fa8eb1',
            'key'  => 'active',
            'name' => 'Active',
        ],
        'inactive' => [
            'id'   => '347e5072-9cd8-42a7-a1be-47f329a9e3eb',
            'key'  => 'inactive',
            'name' => 'Inactive',
        ],
        'invited'  => [
            'id'   => '849deaf1-1ff4-4cd4-9c03-a1c4d9ba0402',
            'key'  => 'invited',
            'name' => 'Invited',
        ],
        'expired'  => [
            'id'   => 'c4136a8c-7cc4-4e30-8712-e47565a5e167',
            'key'  => 'expired',
            'name' => 'Expired',
        ],
    ];

    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function status(OrganizationUser $user): Status {
        // Disabled
        if (!$user->enabled) {
            return $this->getStatus('inactive');
        }

        // Invited?
        if ($user->invited) {
            $last = Invitation::query()
                ->where('organization_id', '=', $user->organization_id)
                ->where('user_id', '=', $user->user_id)
                ->orderByDesc('created_at')
                ->first();

            if ($last && $last->expired_at->isFuture()) {
                return $this->getStatus('invited');
            } else {
                return $this->getStatus('expired');
            }
        }

        // Active
        return $this->getStatus('active');
    }

    protected function getStatus(string $key): Status {
        $status = self::$statuses[$key] ?? [];
        $status = (new Status())->forceFill(array_merge([
            'key'         => $key,
            'name'        => $key,
            'object_type' => (new UserModel())->getMorphClass(),
        ], $status));

        return $status;
    }
}
