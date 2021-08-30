<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User as UserModel;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Commands\UsersImporter
 */
class UsersImporterTest extends TestCase {

    public function testImportFull(): void {
        // Prepare
        $organization = Organization::factory()->create([
            'id' => 'c0200a6c-1b8a-4365-9f1b-32d753194336',
        ]);
        $role         = Role::factory()->create([
            'id' => 'c0200a6c-1b8a-4365-9f1b-32d753194337',
        ]);
        $keycloakUser = new User([
            'id'            => 'c0200a6c-1b8a-4365-9f1b-32d753194335',
            'email'         => 'test@example.com',
            'firstName'     => 'first',
            'lastName'      => 'last',
            'emailVerified' => false,
            'groups'        => [
                'c0200a6c-1b8a-4365-9f1b-32d753194336',
                'c0200a6c-1b8a-4365-9f1b-32d753194337',
            ],
        ]);
        $this->override(Client::class, static function (MockInterface $mock) use ($keycloakUser): void {
            $mock
                ->shouldReceive('getUsers')
                ->once()
                ->andReturns([
                    $keycloakUser,
                ]);
        });

        // call
        $importer = $this->app->make(UsersImporter::class);
        $importer->import(null, 1, 1, 1);

        $user = UserModel::query()
            ->with(['organizations', 'roles'])
            ->whereKey($keycloakUser->id)
            ->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->email_verified);
        $this->assertEquals($user->given_name, $keycloakUser->firstName);
        $this->assertEquals($user->family_name, $keycloakUser->lastName);
        $this->assertEquals($user->email, $keycloakUser->email);

        // Organization
        $this->assertCount(1, $user->organizations);
        $this->assertEquals($organization->getKey(), $user->organizations->first()->getKey());

        // Role
        $this->assertCount(1, $user->roles);
        $this->assertEquals($role->getKey(), $user->roles->first()->getKey());
    }
}
