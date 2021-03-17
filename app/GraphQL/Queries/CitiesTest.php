<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\City;
use App\Models\Country;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomerTypes
 */
class CitiesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $countriesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $countryId = 'wrong';

        if ($countriesFactory) {
            $countryId = $countriesFactory($this)->id;
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query cities($id: ID!) {
                    cities(country_id: $id) {
                        id
                        name
                        country_id
                        country {
                            id
                            name
                            code
                        }
                    }
                }
            ', ['id' => $countryId])
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
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('cities', self::class, [
                        [
                            'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name'       => 'city name',
                            'country_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'country'    => [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'country name',
                                'code' => 'c1',
                            ],
                        ],
                    ]),
                    static function (): Country {
                        $country = Country::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'country name',
                            'code' => 'c1',
                        ]);
                        City::factory()->create([
                            'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name'       => 'city name',
                            'country_id' => $country->id,
                        ]);
                        return $country;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
