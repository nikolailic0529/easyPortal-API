<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Enums\UserType;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
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
            self::assertEquals($type === UserType::local(), User::factory()->make([
                'type' => $type,
            ])->isRoot());
        }
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        // Disable unwanted scopes
        GlobalScopes::setDisabled(
            OwnedByOrganizationScope::class,
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
}
