<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Importer\UsersImporter
 */
class UsersImporterTest extends TestCase {
    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Prepare
        $organization = $this->setOrganization(Organization::factory()->create([
            'keycloak_group_id' => 'c0200a6c-1b8a-4365-9f1b-32d753194336',
        ]));
        $role         = Role::factory()->create([
            'id'              => 'c0200a6c-1b8a-4365-9f1b-32d753194337',
            'organization_id' => $organization->getKey(),
        ]);
        $keycloakUser = new KeyCloakUser([
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

        $this->override(Client::class, static function (MockInterface $mock) use ($keycloakUser): void {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->makePartial();
            $mock
                ->shouldReceive('call')
                ->never();
            $mock
                ->shouldReceive('getUsers')
                ->once()
                ->andReturns([
                    $keycloakUser,
                ]);
            $mock
                ->shouldReceive('usersCount')
                ->once()
                ->andReturns(1);
        });

        // call
        $importer = $this->app->make(UsersImporter::class);
        $importer->import(null, 1, 1);

        $user = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($keycloakUser) {
                return User::query()
                    ->with(['organizations'])
                    ->whereKey($keycloakUser->id)
                    ->first();
            },
        );
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
        $this->assertContains(
            $organization->getKey(),
            $user->organizations->pluck('organization_id'),
        );

        // Role
        $this->assertContains(
            $role->getKey(),
            $user->organizations->pluck('role_id'),
        );
    }
}
