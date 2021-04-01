<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Organization as ModelsOrganization;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\NotFound;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Organization
 */
class OrganizationTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        // Test
        $this->graphQL(/** @lang GraphQL */ '{
            organization {
                id
                subdomain
                name
                locale
                branding_dark_theme
                branding_primary_color
                branding_secondary_color
                branding_logo
                branding_favicon
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
                    lat
                    lng
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
        return (new CompositeDataProvider(
            new ArrayDataProvider([
                'no tenant' => [
                    new ExpectedFinal(new NotFound()),
                    static function (): ?Organization {
                        return null;
                    },
                ],
                'tenant'    => [
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
                                'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'    => 'state1',
                                'postcode' => '19911',
                                'line_one' => 'line_one_data',
                                'line_two' => 'line_two_data',
                                'lat'      => '47.91634204',
                                'lng'      => '-2.26318359',
                            ])
                            ->create([
                                'id'                       => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name'                     => 'org1',
                                'subdomain'                => 'org1',
                                'locale'                   => 'en',
                                'branding_dark_theme'      => false,
                                'branding_primary_color'   => '#FFFFFF',
                                'branding_secondary_color' => '#000000',
                            ]);
                        return $organization;
                    },
                ],
            ]),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('organization', Organization::class, [
                        'id'                       => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'name'                     => 'org1',
                        'subdomain'                => 'org1',
                        'locale'                   => 'en',
                        'branding_dark_theme'      => false,
                        'branding_primary_color'   => '#FFFFFF',
                        'branding_secondary_color' => '#000000',
                        'branding_logo'            => null,
                        'branding_favicon'         => null,
                        'branding_secondary_color' => '#000000',
                        'currency'                 => [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name' => 'currency1',
                            'code' => 'CUR',
                        ],
                        'locations'                => [
                            [
                                'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'    => 'state1',
                                'postcode' => '19911',
                                'line_one' => 'line_one_data',
                                'line_two' => 'line_two_data',
                                'lat'      => '47.91634204',
                                'lng'      => '-2.26318359',
                            ],
                        ],
                    ]),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
