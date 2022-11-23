<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Data\City;
use App\Models\Data\Country;
use App\Models\Data\Location;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class LocationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     *
     * @param OrganizationFactory        $orgFactory
     * @param UserFactory                $userFactory
     * @param Closure(static): void|null $factory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        if ($factory) {
            $factory($this);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    locations {
                        id
                        postcode
                        state
                        line_one
                        line_two
                        latitude
                        longitude
                        city_id
                        city {
                            id
                        }
                        country_id
                        country {
                            id
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
        return (new CompositeDataProvider(
            new AuthOrgDataProvider('locations'),
            new AuthMeDataProvider('locations'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('locations', [
                        [
                            'id'         => 'c2324fe7-7723-4c93-944c-7933ebf573d1',
                            'state'      => 'state1',
                            'postcode'   => '19911',
                            'line_one'   => 'line_one_data',
                            'line_two'   => 'line_two_data',
                            'latitude'   => 47.91634204,
                            'longitude'  => -2.26318359,
                            'country_id' => '793fdd28-2962-46d0-bf7e-65587e39552e',
                            'country'    => [
                                'id' => '793fdd28-2962-46d0-bf7e-65587e39552e',
                            ],
                            'city_id'    => '79f156b5-f76d-4b86-ae9b-c35d0a9d3aea',
                            'city'       => [
                                'id' => '79f156b5-f76d-4b86-ae9b-c35d0a9d3aea',
                            ],
                        ],
                    ]),
                    static function (): void {
                        $country = Country::factory()->create([
                            'id' => '793fdd28-2962-46d0-bf7e-65587e39552e',
                        ]);
                        $city    = City::factory()->create([
                            'id'         => '79f156b5-f76d-4b86-ae9b-c35d0a9d3aea',
                            'country_id' => $country,
                        ]);

                        Location::factory()->create([
                            'id'         => 'c2324fe7-7723-4c93-944c-7933ebf573d1',
                            'state'      => 'state1',
                            'postcode'   => '19911',
                            'line_one'   => 'line_one_data',
                            'line_two'   => 'line_two_data',
                            'latitude'   => '47.91634204',
                            'longitude'  => '-2.26318359',
                            'country_id' => $country,
                            'city_id'    => $city,
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
