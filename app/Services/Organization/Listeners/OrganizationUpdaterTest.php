<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Models\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Schema\Types\Company;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Organization\Listeners\OrganizationUpdater
 */
class OrganizationUpdaterTest extends TestCase {
    /**
     * @covers ::getEvents
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
            'keycloakName'            => $this->faker->word(),
            'keycloakGroupId'         => $this->faker->word(),
            'keycloakClientScopeName' => $this->faker->word(),
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        self::assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals(OrganizationType::reseller(), $organization->type);
        self::assertEquals($company->keycloakName, $organization->keycloak_name);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        self::assertEquals($company->keycloakClientScopeName, $organization->keycloak_scope);
    }

    /**
     * @covers ::handle
     */
    public function testHandleKeycloakGroupIdIsNull(): void {
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'            => null,
            'keycloakGroupId'         => null,
            'keycloakClientScopeName' => null,
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertNull($organization->keycloak_name);
        self::assertNull($organization->keycloak_scope);
        self::assertNull($organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleOrganizationExists(): void {
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'            => $this->faker->word(),
            'keycloakGroupId'         => $this->faker->word(),
            'keycloakClientScopeName' => $this->faker->word(),
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        Organization::factory()->create([
            'id'                => $reseller->getKey(),
            'name'              => 'anothertestreseller',
            'keycloak_name'     => 'anothertestreseller',
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_name);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        self::assertEquals($company->keycloakClientScopeName, $organization->keycloak_scope);
    }

    /**
     * @covers ::handle
     */
    public function testHandleKeycloakPropertiesCannotBeReset(): void {
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'            => null,
            'keycloakGroupId'         => null,
            'keycloakClientScopeName' => null,
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        Organization::factory()->create([
            'id'                => $reseller->getKey(),
            'keycloak_name'     => 'anothertestreseller',
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals('anothertestreseller', $organization->keycloak_name);
        self::assertEquals('anothertestreseller', $organization->keycloak_scope);
        self::assertEquals('anothertestgroup', $organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleSoftDeleteOrganizationExists(): void {
        $reseller = Reseller::factory()->create(['name' => 'Test Reseller']);
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'            => $this->faker->word(),
            'keycloakGroupId'         => $this->faker->word(),
            'keycloakClientScopeName' => $this->faker->word(),
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        Organization::factory()->create([
            'keycloak_name'     => 'anothertestreseller',
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
        ]);
        Organization::factory()->create([
            'id'                => $reseller->getKey(),
            'keycloak_name'     => 'anothertestreseller',
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
            'deleted_at'        => Date::now(),
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertFalse($organization->trashed());
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_name);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        self::assertEquals($company->keycloakClientScopeName, $organization->keycloak_scope);
    }

    /**
     * @covers ::handle
     */
    public function testHandleKeycloakNameIsUsedByAnotherOrganization(): void {
        $reseller          = Reseller::factory()->make();
        $updater           = $this->app->make(OrganizationUpdater::class);
        $company           = new Company([
            'keycloakName'            => $this->faker->word(),
            'keycloakGroupId'         => $this->faker->word(),
            'keycloakClientScopeName' => $this->faker->word(),
        ]);
        $event             = new ResellerUpdated($reseller, $company);
        $scopeOrganization = Organization::factory()->create([
            'keycloak_name' => $company->keycloakName,
        ]);

        Organization::factory()->create([
            'keycloak_group_id' => $company->keycloakGroupId,
        ]);

        self::assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();
        $fresh        = $scopeOrganization->fresh();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_name);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        self::assertEquals($company->keycloakClientScopeName, $organization->keycloak_scope);
        self::assertNotNull($fresh);
        self::assertNull($fresh->keycloak_name);
        self::assertNull($fresh->keycloak_scope);
        self::assertNull($fresh->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleKeycloakScopeNameIsUsedByAnotherOrganization(): void {
        $reseller          = Reseller::factory()->make();
        $updater           = $this->app->make(OrganizationUpdater::class);
        $company           = new Company([
            'keycloakName'            => $this->faker->word(),
            'keycloakGroupId'         => $this->faker->word(),
            'keycloakClientScopeName' => $this->faker->word(),
        ]);
        $event             = new ResellerUpdated($reseller, $company);
        $scopeOrganization = Organization::factory()->create([
            'keycloak_scope' => $company->keycloakClientScopeName,
        ]);

        Organization::factory()->create([
            'keycloak_group_id' => $company->keycloakGroupId,
        ]);

        self::assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();
        $fresh        = $scopeOrganization->fresh();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_name);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        self::assertEquals($company->keycloakClientScopeName, $organization->keycloak_scope);
        self::assertNotNull($fresh);
        self::assertNull($fresh->keycloak_name);
        self::assertNull($fresh->keycloak_scope);
        self::assertNull($fresh->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleKeycloakGroutIsUsedByAnotherOrganization(): void {
        $reseller          = Reseller::factory()->make();
        $updater           = $this->app->make(OrganizationUpdater::class);
        $company           = new Company([
            'keycloakName'            => $this->faker->word(),
            'keycloakGroupId'         => $this->faker->word(),
            'keycloakClientScopeName' => $this->faker->word(),
        ]);
        $event             = new ResellerUpdated($reseller, $company);
        $scopeOrganization = Organization::factory()->create([
            'keycloak_scope' => $company->keycloakClientScopeName,
        ]);

        Organization::factory()->create([
            'keycloak_group_id' => $company->keycloakGroupId,
        ]);

        self::assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();
        $fresh        = $scopeOrganization->fresh();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_name);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        self::assertEquals($company->keycloakClientScopeName, $organization->keycloak_scope);
        self::assertNotNull($fresh);
        self::assertNull($fresh->keycloak_name);
        self::assertNull($fresh->keycloak_scope);
        self::assertNull($fresh->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleUpdateBranding(): void {
        // With Branding
        $reseller = Reseller::factory()->make();
        $updater  = $this->app->make(OrganizationUpdater::class);
        $company  = new Company([
            'keycloakName'    => $this->faker->word(),
            'keycloakGroupId' => $this->faker->word(),
            'brandingData'    => [
                'brandingMode'          => $this->faker->randomElement([' true ', ' false ']),
                'defaultLogoUrl'        => " {$this->faker->url()} ",
                'defaultMainColor'      => ' not a color ',
                'favIconUrl'            => " {$this->faker->url()} ",
                'logoUrl'               => " {$this->faker->url()} ",
                'mainColor'             => " {$this->faker->hexColor()} ",
                'mainImageOnTheRight'   => " {$this->faker->url()} ",
                'secondaryColor'        => " {$this->faker->hexColor()} ",
                'secondaryColorDefault' => " {$this->faker->hexColor()} ",
                'useDefaultFavIcon'     => " {$this->faker->url()} ",
                'resellerAnalyticsCode' => " {$this->faker->word()} ",
                'mainHeadingText'       => [
                    [
                        'language_code' => ' en ',
                        'text'          => " {$this->faker->sentence()} ",
                    ],
                    [
                        'language_code' => ' unknown ',
                        'text'          => " {$this->faker->sentence()} ",
                    ],
                ],
                'underlineText'         => [
                    [
                        'language_code' => ' en ',
                        'text'          => " {$this->faker->text()} ",
                    ],
                    [
                        'language_code' => ' unknown ',
                        'text'          => " {$this->faker->text()} ",
                    ],
                ],
            ],
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();
        $branding     = $company->brandingData;

        self::assertNotNull($organization);
        self::assertNotNull($branding);
        self::assertEquals(
            $branding->brandingMode,
            $organization->branding_dark_theme,
        );
        self::assertEquals(
            $branding->defaultLogoUrl,
            $organization->branding_default_logo_url,
        );
        self::assertEquals(
            $branding->defaultMainColor,
            $organization->branding_default_main_color,
        );
        self::assertEquals(
            $branding->favIconUrl,
            $organization->branding_favicon_url,
        );
        self::assertEquals(
            $branding->logoUrl,
            $organization->branding_logo_url,
        );
        self::assertEquals(
            $branding->mainColor,
            $organization->branding_main_color,
        );
        self::assertEquals(
            $branding->mainImageOnTheRight,
            $organization->branding_welcome_image_url,
        );
        self::assertEquals(
            $branding->secondaryColor,
            $organization->branding_secondary_color,
        );
        self::assertEquals(
            $branding->secondaryColorDefault,
            $organization->branding_default_secondary_color,
        );
        self::assertEquals(
            $branding->useDefaultFavIcon,
            $organization->branding_default_favicon_url,
        );
        self::assertEquals(
            $branding->resellerAnalyticsCode,
            $organization->analytics_code,
        );
        self::assertEquals(
            [
                'en_GB'   => $branding->mainHeadingText[0]->text ?? null,
                'unknown' => $branding->mainHeadingText[1]->text ?? null,
            ],
            $organization->branding_welcome_heading->toArray(),
        );
        self::assertEquals(
            [
                'en_GB'   => $branding->underlineText[0]->text ?? null,
                'unknown' => $branding->underlineText[1]->text ?? null,
            ],
            $organization->branding_welcome_underline->toArray(),
        );

        // Without branding
        $company = new Company();
        $event   = new ResellerUpdated($reseller, $company);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertNotNull($organization->branding_dark_theme);
        self::assertNotNull($organization->branding_default_logo_url);
        self::assertNull($organization->branding_default_main_color);
        self::assertNotNull($organization->branding_favicon_url);
        self::assertNotNull($organization->branding_logo_url);
        self::assertNotNull($organization->branding_main_color);
        self::assertNotNull($organization->branding_welcome_heading);
        self::assertNotNull($organization->branding_welcome_image_url);
        self::assertNotNull($organization->branding_secondary_color);
        self::assertNotNull($organization->branding_default_secondary_color);
        self::assertNotNull($organization->branding_welcome_underline);
        self::assertNotNull($organization->branding_default_favicon_url);
        self::assertNotNull($organization->analytics_code);
    }
}
