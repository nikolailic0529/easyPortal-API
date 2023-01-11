<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Enums\UserType;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Database\Eloquent\Collection;
use Tests\Providers\ModelsProvider;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Models\User
 */
class UserTest extends TestCase {
    public function testIsRoot(): void {
        foreach (UserType::getValues() as $type) {
            self::assertEquals(
                $type === UserType::local(),
                User::factory()->make([
                    'type' => $type,
                ])->isRoot(),
            );
        }
    }

    public function testDelete(): void {
        $models = (new ModelsProvider())($this);
        $model  = $models['user'] ?? null;

        self::assertNotNull($model);
        self::assertModelHasAllRelations($model);

        $model->delete();

        self::assertModelsTrashed(
            [
                'distributor'                   => false,
                'type'                          => false,
                'status'                        => false,
                'coverage'                      => false,
                'country'                       => false,
                'city'                          => false,
                'currency'                      => false,
                'language'                      => false,
                'permission'                    => false,
                'psp'                           => false,
                'tag'                           => false,
                'team'                          => false,
                'oem'                           => false,
                'product'                       => false,
                'productLine'                   => false,
                'productGroup'                  => false,
                'serviceGroup'                  => false,
                'serviceLevel'                  => false,
                'oemGroup'                      => false,
                'location'                      => false,
                'locationReseller'              => false,
                'locationCustomer'              => false,
                'organization'                  => false,
                'organizationRole'              => false,
                'organizationRolePermission'    => false,
                'organizationUser'              => false,
                'organizationChangeRequest'     => false,
                'organizationChangeRequestFile' => false,
                'user'                          => true,
                'userSearch'                    => false,
                'userInvitation'                => false,
                'reseller'                      => false,
                'resellerKpi'                   => false,
                'resellerCustomerKpi'           => false,
                'resellerContact'               => false,
                'resellerContactType'           => false,
                'resellerStatus'                => false,
                'resellerLocation'              => false,
                'resellerLocationType'          => false,
                'resellerChangeRequest'         => false,
                'resellerChangeRequestFile'     => false,
                'resellerCustomer'              => false,
                'customer'                      => false,
                'customerKpi'                   => false,
                'customerContact'               => false,
                'customerContactType'           => false,
                'customerStatus'                => false,
                'customerLocation'              => false,
                'customerLocationType'          => false,
                'customerChangeRequest'         => false,
                'customerChangeRequestFile'     => false,
                'audit'                         => false,
                'asset'                         => false,
                'assetContact'                  => false,
                'assetContactType'              => false,
                'assetCoverage'                 => false,
                'assetTag'                      => false,
                'assetChangeRequest'            => false,
                'assetChangeRequestFile'        => false,
                'assetWarranty'                 => false,
                'quoteRequest'                  => false,
                'quoteRequestAsset'             => false,
                'quoteRequestContact'           => false,
                'quoteRequestContactType'       => false,
                'quoteRequestDocument'          => false,
                'quoteRequestDuration'          => false,
                'quoteRequestFile'              => false,
                'contract'                      => false,
                'contractStatus'                => false,
                'contractEntry'                 => false,
                'contractContact'               => false,
                'contractContactType'           => false,
                'contractChangeRequest'         => false,
                'contractChangeRequestFile'     => false,
                'contractNote'                  => false,
                'contractNoteFile'              => false,
                'quote'                         => false,
                'quoteStatus'                   => false,
                'quoteEntry'                    => false,
                'quoteContact'                  => false,
                'quoteContactType'              => false,
                'quoteChangeRequest'            => false,
                'quoteChangeRequestFile'        => false,
                'quoteNote'                     => false,
                'quoteNoteFile'                 => false,
            ],
            $models,
        );
    }

    public function testGetOrganizations(): void {
        // Disable unwanted scopes
        GlobalScopes::setDisabled(
            OwnedByScope::class,
            true,
        );

        // Create
        $user = User::factory()->create();
        $org  = Organization::factory()->create();

        OrganizationUser::factory()->create([
            'enabled'         => true,
            'user_id'         => $user,
            'organization_id' => $org,
        ]);

        OrganizationUser::factory()->create([
            'enabled'         => false,
            'user_id'         => $user,
            'organization_id' => Organization::factory()->create(),
        ]);

        // Test
        self::assertEquals(
            [$org->getKey()],
            $user->getOrganizations()->map(new GetKey())->values()->all(),
        );
    }

    public function testGetOrganizationPermissions(): void {
        // Disable unwanted scopes
        GlobalScopes::setDisabled(
            OwnedByScope::class,
            true,
        );

        // Create
        $a    = Permission::factory()->create();
        $b    = Permission::factory()->create();
        $org  = Organization::factory()->create();
        $user = User::factory()->create();
        $role = Role::factory()->create([
            'organization_id' => $org,
            'permissions'     => Collection::make([$a, $b]),
        ]);

        OrganizationUser::factory()->create([
            'enabled'         => true,
            'user_id'         => $user,
            'role_id'         => $role,
            'organization_id' => $org,
        ]);

        // Test
        self::assertEqualsCanonicalizing(
            [$a->key, $b->key],
            $user->getOrganizationPermissions($org),
        );
    }

    public function testGetOrganizationPermissionsDisabled(): void {
        // Disable unwanted scopes
        GlobalScopes::setDisabled(
            OwnedByScope::class,
            true,
        );

        // Create
        $a    = Permission::factory()->create();
        $b    = Permission::factory()->create();
        $org  = Organization::factory()->create();
        $user = User::factory()->create();
        $role = Role::factory()->create([
            'organization_id' => $org,
            'permissions'     => Collection::make([$a, $b]),
        ]);

        OrganizationUser::factory()->create([
            'enabled'         => false,
            'user_id'         => $user,
            'role_id'         => $role,
            'organization_id' => $org,
        ]);

        // Test
        self::assertEquals(
            [],
            $user->getOrganizationPermissions($org),
        );
    }

    public function testGetOrganizationPermissionsNoRole(): void {
        // Disable unwanted scopes
        GlobalScopes::setDisabled(
            OwnedByScope::class,
            true,
        );

        // Create
        $org  = Organization::factory()->create();
        $user = User::factory()->create();
        $role = Role::factory()->create([
            'organization_id' => $org,
        ]);

        OrganizationUser::factory()->create([
            'enabled'         => true,
            'user_id'         => $user,
            'role_id'         => $role,
            'organization_id' => $org,
        ]);

        // Test
        self::assertEquals(
            [],
            $user->getOrganizationPermissions($org),
        );
    }
}
