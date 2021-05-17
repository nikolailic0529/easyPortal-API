<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Models\Organization;
use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Organization\Listeners\OrganizationUpdater
 */
class OrganizationUpdaterTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::subscribe
     */
    public function testSubscribe(): void {
        Event::fake()->assertListening(ResellerUpdated::class, OrganizationUpdater::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandleNoOrganization(): void {
        $reseller = Reseller::factory()->create(['name' => 'Test Reseller']);
        $updater  = new OrganizationUpdater();
        $event    = new ResellerUpdated($reseller, Mockery::mock(Company::class));

        Organization::factory()->create([
            'keycloak_scope' => 'testreseller',
        ]);
        Organization::factory()->create([
            'keycloak_scope' => 'testreseller_3',
        ]);
        Organization::factory()->create([
            'keycloak_scope' => 'testreseller_4',
            'deleted_at'     => Date::now(),
        ]);
        Organization::factory()->create([
            'keycloak_scope' => 'testreseller_test_7',
        ]);
        Organization::factory()->create([
            'keycloak_scope' => 'anothertestreseller_2',
        ]);

        $this->assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertEquals('testreseller_5', $organization->keycloak_scope);
    }

    /**
     * @covers ::handle
     */
    public function testHandleOrganizationExists(): void {
        $reseller = Reseller::factory()->create(['name' => 'Test Reseller']);
        $updater  = new OrganizationUpdater();
        $event    = new ResellerUpdated($reseller, Mockery::mock(Company::class));

        Organization::factory()->create([
            'id'             => $reseller->getKey(),
            'keycloak_scope' => 'anothertestreseller',
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertEquals('anothertestreseller', $organization->keycloak_scope);
    }

    /**
     * @covers ::handle
     */
    public function testHandleSoftDeleteOrganizationExists(): void {
        $reseller = Reseller::factory()->create(['name' => 'Test Reseller']);
        $updater  = new OrganizationUpdater();
        $event    = new ResellerUpdated($reseller, Mockery::mock(Company::class));

        Organization::factory()->create([
            'id'             => $reseller->getKey(),
            'deleted_at'     => Date::now(),
            'keycloak_scope' => 'anothertestreseller',
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertFalse($organization->trashed());
        $this->assertEquals('anothertestreseller', $organization->keycloak_scope);
    }

    /**
     * @covers ::normalizeKeycloakScope
     *
     * @dataProvider dataProviderNormalizeKeycloakScope
     */
    public function testNormalizeKeycloakScope(string $expected, string $scope): void {
        $updated = new class() extends OrganizationUpdater {
            public function __construct() {
                // empty
            }

            public function normalizeKeycloakScope(string $scope): string {
                return parent::normalizeKeycloakScope($scope);
            }
        };

        $this->assertEquals($expected, $updated->normalizeKeycloakScope($scope));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderNormalizeKeycloakScope(): array {
        return [
            ['abcdnetworkingsolutions', 'A.B.C.D - Networking & Solutions'],
            ['cubegmbh', 'Cube GmbH'],
            ['drmüllerit', 'Dr. Müller & IT'],
        ];
    }
    // </editor-fold>
}
