<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Models\Organization;
use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Normalizer;
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
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
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
    public function testHandleKeycloakGroupIdIsNull(): void {
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'    => null,
            'keycloakGroupId' => null,
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertNull($organization->keycloak_scope);
        $this->assertNull($organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleOrganizationExists(): void {
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
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
    public function testHandleKeyCloakPropertiesCannotBeReset(): void {
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'    => null,
            'keycloakGroupId' => null,
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
        $this->assertEquals('anothertestreseller', $organization->keycloak_scope);
        $this->assertEquals('anothertestgroup', $organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleSoftDeleteOrganizationExists(): void {
        $reseller = Reseller::factory()->create(['name' => 'Test Reseller']);
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
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
        $updater           = $this->app->make(OrganizationUpdater::class);
        $company           = new Company([
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

    /**
     * @covers ::handle
     */
    public function testHandleUpdateBranding(): void {
        // With Branding
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'    => $this->faker->word,
            'keycloakGroupId' => $this->faker->word,
            'brandingData'    => [
                'brandingMode'          => $this->faker->randomElement([' true ', ' false ']),
                'defaultLogoUrl'        => " {$this->faker->url} ",
                'defaultMainColor'      => ' not a color ',
                'favIconUrl'            => " {$this->faker->url} ",
                'logoUrl'               => " {$this->faker->url} ",
                'mainColor'             => " {$this->faker->hexColor} ",
                'mainHeadingText'       => " {$this->faker->sentence} ",
                'mainImageOnTheRight'   => " {$this->faker->url} ",
                'secondaryColor'        => " {$this->faker->hexColor} ",
                'secondaryColorDefault' => " {$this->faker->hexColor} ",
                'underlineText'         => " {$this->faker->text} ",
                'useDefaultFavIcon'     => " {$this->faker->url} ",
                'resellerAnalyticsCode' => " {$this->faker->word} ",
            ],
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();
        $normalizer   = $this->app->make(Normalizer::class);
        $branding     = $company->brandingData;

        $this->assertNotNull($organization);
        $this->assertEquals(
            $normalizer->boolean($branding->brandingMode),
            $organization->branding_dark_theme,
        );
        $this->assertEquals(
            $normalizer->string($branding->defaultLogoUrl),
            $organization->branding_default_logo_url,
        );
        $this->assertEquals(
            $normalizer->color($branding->defaultMainColor),
            $organization->branding_default_main_color,
        );
        $this->assertEquals(
            $normalizer->string($branding->favIconUrl),
            $organization->branding_favicon_url,
        );
        $this->assertEquals(
            $normalizer->string($branding->logoUrl),
            $organization->branding_logo_url,
        );
        $this->assertEquals(
            $normalizer->color($branding->mainColor),
            $organization->branding_main_color,
        );
        $this->assertEquals(
            $normalizer->string($branding->mainHeadingText),
            $organization->branding_welcome_heading,
        );
        $this->assertEquals(
            $normalizer->string($branding->mainImageOnTheRight),
            $organization->branding_welcome_image_url,
        );
        $this->assertEquals(
            $normalizer->color($branding->secondaryColor),
            $organization->branding_secondary_color,
        );
        $this->assertEquals(
            $normalizer->color($branding->secondaryColorDefault),
            $organization->branding_default_secondary_color,
        );
        $this->assertEquals(
            $normalizer->string($branding->underlineText),
            $organization->branding_welcome_underline,
        );
        $this->assertEquals(
            $normalizer->string($branding->useDefaultFavIcon),
            $organization->branding_default_favicon_url,
        );
        $this->assertEquals(
            $normalizer->string($branding->resellerAnalyticsCode),
            $organization->analytics_code,
        );

        // Without branding
        $company = new Company();
        $event   = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertNotNull($organization->branding_dark_theme);
        $this->assertNotNull($organization->branding_default_logo_url);
        $this->assertNull($organization->branding_default_main_color);
        $this->assertNotNull($organization->branding_favicon_url);
        $this->assertNotNull($organization->branding_logo_url);
        $this->assertNotNull($organization->branding_main_color);
        $this->assertNotNull($organization->branding_welcome_heading);
        $this->assertNotNull($organization->branding_welcome_image_url);
        $this->assertNotNull($organization->branding_secondary_color);
        $this->assertNotNull($organization->branding_default_secondary_color);
        $this->assertNotNull($organization->branding_welcome_underline);
        $this->assertNotNull($organization->branding_default_favicon_url);
        $this->assertNotNull($organization->analytics_code);
    }

    /**
     * @covers ::handle
     */
    public function testHandleUpdateKpi(): void {
        // With KPIs
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'companyKpis' => [
                'totalAssets'               => $this->faker->randomNumber(),
                'activeAssets'              => $this->faker->randomNumber(),
                'activeAssetsPercentage'    => $this->faker->randomFloat(),
                'activeCustomers'           => $this->faker->randomNumber(),
                'newActiveCustomers'        => $this->faker->randomNumber(),
                'activeContracts'           => $this->faker->randomNumber(),
                'activeContractTotalAmount' => $this->faker->randomFloat(),
                'newActiveContracts'        => $this->faker->randomNumber(),
                'expiringContracts'         => $this->faker->randomNumber(),
                'activeQuotes'              => $this->faker->randomNumber(),
                'activeQuotesTotalAmount'   => $this->faker->randomFloat(),
                'newActiveQuotes'           => $this->faker->randomNumber(),
                'expiringQuotes'            => $this->faker->randomNumber(),
            ],
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();
        $normalizer   = $this->app->make(Normalizer::class);
        $kpi          = $company->companyKpis;

        $this->assertNotNull($organization);
        $this->assertEquals(
            (int) $normalizer->number($kpi->totalAssets),
            $organization->kpi_assets_total,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->activeAssets),
            $organization->kpi_assets_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($kpi->activeAssetsPercentage),
            $organization->kpi_assets_covered,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->activeCustomers),
            $organization->kpi_customers_active,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->newActiveCustomers),
            $organization->kpi_customers_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->activeContracts),
            $organization->kpi_contracts_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($kpi->activeContractTotalAmount),
            $organization->kpi_contracts_active_amount,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->newActiveContracts),
            $organization->kpi_contracts_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->expiringContracts),
            $organization->kpi_contracts_expiring,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->activeQuotes),
            $organization->kpi_quotes_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($kpi->activeQuotesTotalAmount),
            $organization->kpi_quotes_active_amount,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->newActiveQuotes),
            $organization->kpi_quotes_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($kpi->expiringQuotes),
            $organization->kpi_quotes_expiring,
        );

        // Without KPIs
        $company = new Company();
        $event   = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        $this->assertNotNull($organization);
        $this->assertNotNull($organization->kpi_assets_total);
        $this->assertNotNull($organization->kpi_assets_active);
        $this->assertNotNull($organization->kpi_assets_covered);
        $this->assertNotNull($organization->kpi_customers_active);
        $this->assertNotNull($organization->kpi_customers_active_new);
        $this->assertNotNull($organization->kpi_contracts_active);
        $this->assertNotNull($organization->kpi_contracts_active_amount);
        $this->assertNotNull($organization->kpi_contracts_active_new);
        $this->assertNotNull($organization->kpi_contracts_expiring);
        $this->assertNotNull($organization->kpi_quotes_active);
        $this->assertNotNull($organization->kpi_quotes_active_amount);
        $this->assertNotNull($organization->kpi_quotes_active_new);
        $this->assertNotNull($organization->kpi_quotes_expiring);
    }
}
