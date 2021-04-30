<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\Models\Organization;
use App\Models\User;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\UserProvider
 */
class UserProviderTest extends TestCase {
    /**
     * @covers ::retrieveById()
     */
    public function testRetrieveById(): void {
        $organization = $this->setTenant(Organization::factory()->create());
        $provider     = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getTenant')
            ->once()
            ->andReturn($organization);

        $a = User::factory()->create();
        $b = User::factory()->create([
            'organization_id' => $organization,
        ]);

        $this->assertNull($provider->retrieveById($a->getKey()));
        $this->assertNotNull($provider->retrieveById($b->getKey()));
    }

    /**
     * @covers ::updateRememberToken
     */
    public function testUpdateRememberToken(): void {

    }

    /**
     * @covers ::retrieveByCredentials
     */
    public function testRetrieveByCredentials(): void {

    }

    /**
     * @covers ::retrieveByToken
     */
    public function testRetrieveByToken(): void {

    }

    /**
     * @covers ::validateCredentials
     */
    public function testValidateCredentials(): void {

    }

    /**
     * @covers ::getToken
     */
    public function testGetToken(): void {

    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void {

    }

    /**
     * @covers ::create
     */
    public function testCreate(): void {

    }

    /**
     * @covers ::getProperties
     */
    public function testGetProperties(): void {

    }

    /**
     * @covers ::getOrganization
     */
    public function testGetOrganization(): void {

    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions(): void {

    }
}
