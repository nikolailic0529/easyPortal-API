<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Organization as ModelsOrganization;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Organization
 */
class OrganizationTest extends TestCase {
    /**
     * @covers ::__invoke
     * @covers ::root
     *
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        bool $isRootOrganization = false,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);

        $this->setUser($userFactory, $organization);

        if ($isRootOrganization) {
            $this->setRootOrganization($organization);
        }

        // Test
        $this->graphQL(/** @lang GraphQL */ '{
            organization {
                id
                name
                root
                locale
                branding_dark_theme
                branding_primary_color
                branding_secondary_color
                branding_logo
                branding_favicon
                website_url
                email
                currency_id
                currency {
                    id
                    name
                    code
                }
                locations {
                    id
                    state
                    postcode
                    line_one
                    line_two
                    latitude
                    longitude
                }
            }
        }')->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new MergeDataProvider([
            'any'        => new CompositeDataProvider(
                new AnyOrganizationDataProvider('organization'),
                new AnyUserDataProvider(),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', null),
                    ],
                ]),
            ),
            'properties' => new CompositeDataProvider(
                new ArrayDataProvider([
                    'organization' => [
                        new Unknown(),
                        static function (TestCase $test): ?ModelsOrganization {
                            $currency     = Currency::factory()->create([
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'name' => 'currency1',
                                'code' => 'CUR',
                            ]);
                            $organization = ModelsOrganization::factory()
                                ->for($currency)
                                ->hasLocations(1, [
                                    'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                    'state'     => 'state1',
                                    'postcode'  => '19911',
                                    'line_one'  => 'line_one_data',
                                    'line_two'  => 'line_two_data',
                                    'latitude'  => '47.91634204',
                                    'longitude' => '-2.26318359',
                                ])
                                ->create([
                                    'id'                       => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                    'name'                     => 'org1',
                                    'locale'                   => 'en',
                                    'branding_dark_theme'      => false,
                                    'branding_primary_color'   => '#FFFFFF',
                                    'branding_secondary_color' => '#000000',
                                    'website_url'              => 'https://www.example.com',
                                    'email'                    => 'test@example.com',
                                ]);

                            return $organization;
                        },
                    ],
                ]),
                new AnyUserDataProvider(),
                new ArrayDataProvider([
                    'ok'   => [
                        new GraphQLSuccess('organization', Organization::class, [
                            'id'                       => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name'                     => 'org1',
                            'root'                     => false,
                            'locale'                   => 'en',
                            'branding_dark_theme'      => false,
                            'branding_primary_color'   => '#FFFFFF',
                            'branding_secondary_color' => '#000000',
                            'branding_logo'            => null,
                            'branding_favicon'         => null,
                            'website_url'              => 'https://www.example.com',
                            'email'                    => 'test@example.com',
                            'currency_id'              => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'currency'                 => [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'name' => 'currency1',
                                'code' => 'CUR',
                            ],
                            'locations'                => [
                                [
                                    'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                    'state'     => 'state1',
                                    'postcode'  => '19911',
                                    'line_one'  => 'line_one_data',
                                    'line_two'  => 'line_two_data',
                                    'latitude'  => '47.91634204',
                                    'longitude' => '-2.26318359',
                                ],
                            ],
                        ]),
                        false,
                    ],
                    'root' => [
                        new GraphQLSuccess('organization', Organization::class, new JsonFragment('root', true)),
                        true,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
