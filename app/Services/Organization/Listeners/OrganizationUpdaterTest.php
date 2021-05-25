<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Models\Organization;
use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Organization\Listeners\OrganizationUpdater
 */
class OrganizationUpdaterTest extends TestCase {
    /**
     * @covers ::subscribe
     */
    public function testSubscribe(): void {
        Event::fake()->assertListening(ResellerUpdated::class, OrganizationUpdater::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $reseller = Reseller::factory()->make();
        $updater  = new OrganizationUpdater();
        $company  = Company::create([
            'keycloakName'    => $this->faker->word,
            'keycloakGroupId' => $this->faker->word,
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        $this->assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertEquals($reseller->name, $organization->name);
        $this->assertEquals($company->keycloakName, $organization->keycloak_scope);
        $this->assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleOrganizationExists(): void {
        $reseller = Reseller::factory()->make();
        $updater  = new OrganizationUpdater();
        $company  = Company::create([
            'keycloakName'    => $this->faker->word,
            'keycloakGroupId' => $this->faker->word,
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        Organization::factory()->create([
            'id'                => $reseller->getKey(),
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertEquals($reseller->name, $organization->name);
        $this->assertEquals($company->keycloakName, $organization->keycloak_scope);
        $this->assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleSoftDeleteOrganizationExists(): void {
        $reseller = Reseller::factory()->create(['name' => 'Test Reseller']);
        $updater  = new OrganizationUpdater();
        $company  = Company::create([
            'keycloakName'    => $this->faker->word,
            'keycloakGroupId' => $this->faker->word,
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        Organization::factory()->create([
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
        ]);
        Organization::factory()->create([
            'id'                => $reseller->getKey(),
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
            'deleted_at'        => Date::now(),
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertFalse($organization->trashed());
        $this->assertEquals($reseller->name, $organization->name);
        $this->assertEquals($company->keycloakName, $organization->keycloak_scope);
        $this->assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleScopeAndGroutIsUsedByAnotherOrganization(): void {
        $reseller          = Reseller::factory()->make();
        $updater           = new OrganizationUpdater();
        $company           = Company::create([
            'keycloakName'    => $this->faker->word,
            'keycloakGroupId' => $this->faker->word,
        ]);
        $event             = new ResellerUpdated($reseller, $company);
        $scopeOrganization = Organization::factory()->create([
            'keycloak_scope' => $company->keycloakName,
        ]);
        $groupOrganization = Organization::factory()->create([
            'keycloak_group_id' => $company->keycloakGroupId,
        ]);

        $this->assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertEquals($reseller->name, $organization->name);
        $this->assertEquals($company->keycloakName, $organization->keycloak_scope);
        $this->assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        $this->assertNull($scopeOrganization->fresh()->keycloak_scope);
        $this->assertNull($groupOrganization->fresh()->keycloak_group_id);
    }
}
