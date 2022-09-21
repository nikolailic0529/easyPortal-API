<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Enums\UserType;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Models\User
 */
class UserTest extends TestCase {
    /**
     * @covers ::isRoot
     */
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

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        // Disable unwanted scopes
        GlobalScopes::setDisabled(
            OwnedByScope::class,
            true,
        );

        // Create
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $orgA,
        ]);

        UserSearch::factory()->create([
            'user_id' => $user,
        ]);

        Invitation::factory()->create([
            'user_id'         => $user,
            'organization_id' => $orgA,
        ]);

        OrganizationUser::factory()->create([
            'user_id'         => $user,
            'organization_id' => $orgA,
        ]);
        OrganizationUser::factory()->create([
            'user_id'         => $user,
            'organization_id' => $orgB,
        ]);

        Note::factory()->create([
            'user_id' => $user,
        ]);

        ChangeRequest::factory()->create([
            'user_id' => $user,
        ]);

        // Pretest
        self::assertModelsCount([
            User::class             => 2,
            UserSearch::class       => 1,
            Invitation::class       => 1,
            Organization::class     => 2,
            OrganizationUser::class => 2,
            Note::class             => 1,
            ChangeRequest::class    => 1,
        ]);

        // Run
        $user->delete();

        // Test
        self::assertModelsCount([
            User::class             => 1,
            UserSearch::class       => 1,
            Invitation::class       => 1,
            Organization::class     => 2,
            OrganizationUser::class => 0,
            Note::class             => 1,
            ChangeRequest::class    => 1,
        ]);
    }

    /**
     * @covers ::getOrganizations
     */
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

    /**
     * @covers ::getOrganizationPermissions
     */
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
            'permissions'     => [$a, $b],
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

    /**
     * @covers ::getOrganizationPermissions
     */
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
            'permissions'     => [$a, $b],
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

    /**
     * @covers ::getOrganizationPermissions
     */
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
