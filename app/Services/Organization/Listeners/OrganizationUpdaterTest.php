<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Models\Organization;
use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Normalizer\Normalizer;
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
            'keycloakName'    => $this->faker->word(),
            'keycloakGroupId' => $this->faker->word(),
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        self::assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_scope);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
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

        self::assertNotNull($organization);
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
            'keycloakName'    => $this->faker->word(),
            'keycloakGroupId' => $this->faker->word(),
        ]);
        $event    = new ResellerUpdated($reseller, $company);

        Organization::factory()->create([
            'id'                => $reseller->getKey(),
            'keycloak_scope'    => 'anothertestreseller',
            'keycloak_group_id' => 'anothertestgroup',
        ]);

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_scope);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleKeycloakPropertiesCannotBeReset(): void {
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

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
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
            'keycloakName'    => $this->faker->word(),
            'keycloakGroupId' => $this->faker->word(),
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

        self::assertNotNull($organization);
        self::assertFalse($organization->trashed());
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_scope);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
    }

    /**
     * @covers ::handle
     */
    public function testHandleScopeAndGroutIsUsedByAnotherOrganization(): void {
        $reseller          = Reseller::factory()->make();
        $updater           = $this->app->make(OrganizationUpdater::class);
        $company           = new Company([
            'keycloakName'    => $this->faker->word(),
            'keycloakGroupId' => $this->faker->word(),
        ]);
        $event             = new ResellerUpdated($reseller, $company);
        $scopeOrganization = Organization::factory()->create([
            'keycloak_scope' => $company->keycloakName,
        ]);
        $groupOrganization = Organization::factory()->create([
            'keycloak_group_id' => $company->keycloakGroupId,
        ]);

        self::assertFalse(Organization::query()->withTrashed()->whereKey($reseller->getKey())->exists());

        $updater->handle($event);

        $organization = Organization::query()->whereKey($reseller->getKey())->first();

        self::assertNotNull($organization);
        self::assertEquals($reseller->name, $organization->name);
        self::assertEquals($company->keycloakName, $organization->keycloak_scope);
        self::assertEquals($company->keycloakGroupId, $organization->keycloak_group_id);
        self::assertNull($scopeOrganization->fresh()->keycloak_scope);
        self::assertNull($groupOrganization->fresh()->keycloak_group_id);
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
        $normalizer   = $this->app->make(Normalizer::class);
        $branding     = $company->brandingData;

        self::assertNotNull($organization);
        self::assertEquals(
            $normalizer->boolean($branding->brandingMode),
            $organization->branding_dark_theme,
        );
        self::assertEquals(
            $normalizer->string($branding->defaultLogoUrl),
            $organization->branding_default_logo_url,
        );
        self::assertEquals(
            $normalizer->color($branding->defaultMainColor),
            $organization->branding_default_main_color,
        );
        self::assertEquals(
            $normalizer->string($branding->favIconUrl),
            $organization->branding_favicon_url,
        );
        self::assertEquals(
            $normalizer->string($branding->logoUrl),
            $organization->branding_logo_url,
        );
        self::assertEquals(
            $normalizer->color($branding->mainColor),
            $organization->branding_main_color,
        );
        self::assertEquals(
            $normalizer->string($branding->mainImageOnTheRight),
            $organization->branding_welcome_image_url,
        );
        self::assertEquals(
            $normalizer->color($branding->secondaryColor),
            $organization->branding_secondary_color,
        );
        self::assertEquals(
            $normalizer->color($branding->secondaryColorDefault),
            $organization->branding_default_secondary_color,
        );
        self::assertEquals(
            $normalizer->string($branding->useDefaultFavIcon),
            $organization->branding_default_favicon_url,
        );
        self::assertEquals(
            $normalizer->string($branding->resellerAnalyticsCode),
            $organization->analytics_code,
        );
        self::assertEquals(
            [
                'en_GB'   => $normalizer->string($branding->mainHeadingText[0]->text ?? null),
                'unknown' => $normalizer->string($branding->mainHeadingText[1]->text ?? null),
            ],
            $organization->branding_welcome_heading->toArray(),
        );
        self::assertEquals(
            [
                'en_GB'   => $normalizer->string($branding->underlineText[0]->text ?? null),
                'unknown' => $normalizer->string($branding->underlineText[1]->text ?? null),
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
