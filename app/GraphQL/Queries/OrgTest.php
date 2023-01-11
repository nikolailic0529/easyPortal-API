<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\ChangeRequest;
use App\Models\Data\Currency;
use App\Models\Data\Location;
use App\Models\Enums\OrganizationType;
use App\Models\Kpi;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\DataProviders\GraphQL\Users\UnknownUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\Providers\Organizations\OrganizationProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function array_merge;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Org
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class OrgTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param SettingsFactory                             $settingsFactory
     * @param Closure(static, ?Organization, ?User): void $orgCallback
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $orgCallback = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        if ($orgCallback) {
            $orgCallback($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                {
                    org {
                        id
                        name
                        type
                        locale
                        website_url
                        email
                        analytics_code
                        currency_id
                        timezone
                        currency {
                            id
                            name
                            code
                        }
                        branding {
                            logo_url
                        }
                        kpi {
                            assets_total
                        }
                        headquarter {
                            location_id
                        }
                    }
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }

    public function testHeadquarter(): void {
        // Prepare
        $org         = $this->setOrganization(Organization::factory()->make());
        $type        = $this->faker->uuid();
        $reseller    = Reseller::factory()->create([
            'id' => $org->getKey(),
        ]);
        $headquarter = ResellerLocation::factory()
            ->hasTypes(1, [
                'id'   => $type,
                'name' => 'headquarter',
            ])
            ->create([
                'reseller_id' => $reseller,
            ]);

        // Test
        $this->setSettings([
            'ep.headquarter_type' => $type,
        ]);

        $actual = $this->app->make(Org::class)->headquarter($org);

        self::assertEquals($headquarter, $actual);
    }

    public function testHeadquarterNoType(): void {
        // Prepare
        $org      = $this->setOrganization(Organization::factory()->make());
        $reseller = Reseller::factory()->create([
            'id' => $org->getKey(),
        ]);

        ResellerLocation::factory()->create([
            'reseller_id' => $reseller,
        ]);
        ResellerLocation::factory()->create([
            'reseller_id' => $reseller,
        ]);

        // Test
        $this->setSettings([
            'ep.headquarter_type' => null,
        ]);

        $actual   = $this->app->make(Org::class)->headquarter($org);
        $expected = $reseller->locations->first();

        self::assertNotNull($expected);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderOrganization
     *
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param Closure(static, ?Organization, ?User): void $factory
     */
    public function testOrganization(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($factory) {
            $factory($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                {
                    org {
                        organization {
                            id
                            changeRequests {
                                id
                                subject
                                message
                                from
                                to
                                cc
                                bcc
                                user_id
                                files {
                                    name
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $expected = [
            'id'             => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
            'name'           => 'org1',
            'locale'         => 'en',
            'website_url'    => 'https://www.example.com',
            'email'          => 'test@example.com',
            'analytics_code' => 'analytics_code',
            'currency_id'    => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
            'timezone'       => 'Europe/London',
            'currency'       => [
                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                'name' => 'currency1',
                'code' => 'CUR',
            ],
            'branding'       => [
                'logo_url' => 'https://www.example.com/logo.png',
            ],
            'kpi'            => null,
            'headquarter'    => null,
        ];

        return (new MergeDataProvider([
            'any'        => new CompositeDataProvider(
                new UnknownOrgDataProvider(),
                new UnknownUserDataProvider(),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('org'),
                    ],
                ]),
            ),
            'properties' => new CompositeDataProvider(
                new ArrayDataProvider([
                    'org' => [
                        new UnknownValue(),
                        static function (): Organization {
                            $currency = Currency::factory()->create([
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'name' => 'currency1',
                                'code' => 'CUR',
                            ]);
                            $org      = Organization::factory()
                                ->for($currency)
                                ->create([
                                    'id'                => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                    'name'              => 'org1',
                                    'locale'            => 'en',
                                    'website_url'       => 'https://www.example.com',
                                    'email'             => 'test@example.com',
                                    'analytics_code'    => 'analytics_code',
                                    'branding_logo_url' => 'https://www.example.com/logo.png',
                                    'timezone'          => 'Europe/London',
                                ]);

                            return $org;
                        },
                    ],
                ]),
                new UnknownUserDataProvider(),
                new ArrayDataProvider([
                    'unknown'  => [
                        new GraphQLSuccess('org', array_merge($expected, [
                            'type' => OrganizationType::reseller(),
                        ])),
                        null,
                        static function (TestCase $test, Organization $org): void {
                            $org->type = OrganizationType::reseller();
                        },
                    ],
                    'reseller' => [
                        new GraphQLSuccess('org', array_merge($expected, [
                            'type'        => OrganizationType::reseller(),
                            'kpi'         => [
                                'assets_total' => 1,
                            ],
                            'headquarter' => [
                                'location_id' => '1afffd34-de59-48e0-9689-57be151af10c',
                            ],
                        ])),
                        [
                            'ep.headquarter_type' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $org): void {
                            $org->type = OrganizationType::reseller();
                            $kpi       = Kpi::factory()->create([
                                'assets_total' => 1,
                            ]);
                            $reseller  = Reseller::factory()->ownedBy($org)->create([
                                'id'     => $org->getKey(),
                                'kpi_id' => $kpi,
                            ]);
                            $location  = Location::factory()->create([
                                'id' => '1afffd34-de59-48e0-9689-57be151af10c',
                            ]);

                            ResellerLocation::factory()
                                ->hasTypes(1, [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'name' => 'headquarter',
                                ])
                                ->create([
                                    'reseller_id' => $reseller,
                                    'location_id' => $location,
                                ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<string, mixed>
     */
    public function dataProviderOrganization(): array {
        return (new CompositeDataProvider(
            new ArrayDataProvider([
                'org' => [
                    new UnknownValue(),
                    new OrganizationProvider('e0244b6d-35b0-4e15-9e38-6478a1e98eb1'),
                ],
            ]),
            new OrgUserDataProvider(
                'org',
                [
                    'org-administer',
                ],
                '22ca602c-ae8c-41b0-83a0-c6a5e7cf3538',
            ),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('org', [
                        'organization' => [
                            'id'             => 'e0244b6d-35b0-4e15-9e38-6478a1e98eb1',
                            'changeRequests' => [
                                [
                                    'id'      => '4acb3b3a-82b4-4ae4-8413-cb87c0fed513',
                                    'user_id' => '22ca602c-ae8c-41b0-83a0-c6a5e7cf3538',
                                    'subject' => 'Subject A',
                                    'message' => 'Change Request A',
                                    'from'    => 'user@example.com',
                                    'to'      => ['test@example.com'],
                                    'cc'      => ['cc@example.com'],
                                    'bcc'     => ['bcc@example.com'],
                                    'files'   => [
                                        [
                                            'name' => 'documents.csv',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    static function (TestCase $test, Organization $org, User $user): void {
                        ChangeRequest::factory()
                            ->ownedBy($org)
                            ->for($user)
                            ->hasFiles(1, [
                                'name' => 'documents.csv',
                            ])
                            ->create([
                                'id'          => '4acb3b3a-82b4-4ae4-8413-cb87c0fed513',
                                'object_id'   => $org->getKey(),
                                'object_type' => $org->getMorphClass(),
                                'message'     => 'Change Request A',
                                'subject'     => 'Subject A',
                                'from'        => 'user@example.com',
                                'to'          => ['test@example.com'],
                                'cc'          => ['cc@example.com'],
                                'bcc'         => ['bcc@example.com'],
                            ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
