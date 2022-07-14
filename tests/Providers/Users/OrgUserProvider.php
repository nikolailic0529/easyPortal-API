<?php declare(strict_types = 1);

namespace Tests\Providers\Users;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Tests\TestCase;

class OrgUserProvider extends UserProvider {
    public function __invoke(TestCase $test, ?Organization $organization): User {
        $user = parent::__invoke($test, $organization);

        if ($organization) {
            OrganizationUser::factory()->create([
                'organization_id' => $organization,
                'user_id'         => $user,
                'enabled'         => true,
            ]);
        }

        return $user;
    }
}
