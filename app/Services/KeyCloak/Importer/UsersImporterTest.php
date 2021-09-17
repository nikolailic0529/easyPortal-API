<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User as UserModel;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\KeyCloak\Client\UsersIterator;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Importer\UsersImporter
 */
class UsersImporterTest extends TestCase {

    public function testImportFull(): void {
        // Prepare
        $organization = Organization::factory()->create([
            'keycloak_group_id' => 'c0200a6c-1b8a-4365-9f1b-32d753194336',
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
            'enabled'       => true,
            'groups'        => [
                'c0200a6c-1b8a-4365-9f1b-32d753194336',
                'c0200a6c-1b8a-4365-9f1b-32d753194337',
            ],
            'attributes'    => [
                'contact_email'  => [
                    'test@gmail.com',
                ],
                'academic_title' => [
                    'academic_title',
                ],
                'title'          => [
                    'Mr',
                ],
                'office_phone'   => [
                    '01000230232',
                ],
                'mobile_phone'   => [
                    '0100023023232',
                ],
                'department'     => [
                    'hr',
                ],
                'job_title'      => [
                    'manger',
                ],
                'company'        => [
                    'EP',
                ],
                'phone'          => [
                    '0100023023235',
                ],
                'photo'          => [
                    'http://example.com/photo.jpg',
                ],
            ],
        ]);
        $client       = Mockery::mock(Client::class);
        $client->makePartial();
        $client
            ->shouldReceive('getUsers')
            ->once()
            ->andReturns([
                $keycloakUser,
            ]);
        $client
            ->shouldReceive('usersCount')
            ->once()
            ->andReturns(1);
        $iterator = new UsersIterator($client);
        $client
            ->shouldReceive('getUsersIterator')
            ->once()
            ->andReturns($iterator);
        $this->app->instance(Client::class, $client);

        // call
        $importer = $this->app->make(UsersImporter::class);
        $importer->import(null, 1, 1);

        $user = UserModel::query()
            ->with(['organizations', 'roles'])
            ->whereKey($keycloakUser->id)
            ->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->email_verified);
        $this->assertTrue($user->enabled);
        $this->assertEquals($user->given_name, $keycloakUser->firstName);
        $this->assertEquals($user->family_name, $keycloakUser->lastName);
        $this->assertEquals($user->email, $keycloakUser->email);

        // profile
        $this->assertEquals($user->office_phone, $keycloakUser->attributes['office_phone'][0]);
        $this->assertEquals($user->contact_email, $keycloakUser->attributes['contact_email'][0]);
        $this->assertEquals($user->title, $keycloakUser->attributes['title'][0]);
        $this->assertEquals($user->mobile_phone, $keycloakUser->attributes['mobile_phone'][0]);
        $this->assertEquals($user->department, $keycloakUser->attributes['department'][0]);
        $this->assertEquals($user->job_title, $keycloakUser->attributes['job_title'][0]);
        $this->assertEquals($user->phone, $keycloakUser->attributes['phone'][0]);
        $this->assertEquals($user->company, $keycloakUser->attributes['company'][0]);
        $this->assertEquals($user->photo, $keycloakUser->attributes['photo'][0]);

        // Organization
        $this->assertCount(1, $user->organizations);
        $this->assertEquals($organization->getKey(), $user->organizations->first()->getKey());

        // Role
        $this->assertCount(1, $user->roles);
        $this->assertEquals($role->getKey(), $user->roles->first()->getKey());
    }
}
