<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\City;
use App\Models\Country;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\AnyTenantDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomerTypes
 */
class CitiesTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        Closure $countryFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $countryId = 'wrong';

        if ($countryFactory) {
            $countryId = $countryFactory($this)->getKey();
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Flush
        $this->flushQueryLog();

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query cities($country_id: ID!) {
                    cities(where:{ country_id: { eq: $country_id } }) {
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
            ', ['country_id' => $countryId])
            ->assertThat($expected);

        // Eager Loading
        if ($expected instanceof GraphQLSuccess) {
            $this->assertCount(3, $this->getQueryLog());
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AnyTenantDataProvider('cities'),
            new AnyUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('cities', self::class, [
                        [
                            'id'         => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'name'       => 'Translated (locale)',
                            'country_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'country'    => [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'country name',
                                'code' => 'c1',
                            ],
                        ],
                        [
                            'id'         => '7d4795e9-c687-4ef8-acd7-017afe63bb50',
                            'name'       => 'Translated (country + fallback)',
                            'country_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'country'    => [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'country name',
                                'code' => 'c1',
                            ],
                        ],
                        [
                            'id'         => '9d5bf4eb-f44e-4c2f-9180-5fc78e75d928',
                            'name'       => 'Translated (country)',
                            'country_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'country'    => [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'country name',
                                'code' => 'c1',
                            ],
                        ],
                        [
                            'id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'name'       => 'Translated (fallback)',
                            'country_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'country'    => [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'country name',
                                'code' => 'c1',
                            ],
                        ],
                        [
                            'id'         => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'       => 'No translation',
                            'country_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'country'    => [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'country name',
                                'code' => 'c1',
                            ],
                        ],
                    ]),
                    static function (TestCase $test): string {
                        $translator = $test->app()->make(Translator::class);
                        $fallback   = $translator->getFallback();
                        $locale     = $test->app()->getLocale();
                        $model      = (new City())->getMorphClass();

                        $translator->addLines([
                            "models.{$model}.name.translated" => 'Translated (locale)',
                        ], $locale);

                        $translator->addLines([
                            "models.{$model}.name.c1.translated-country" => 'Translated (country)',
                        ], $locale);

                        $translator->addLines([
                            "models.{$model}.name.translated-fallback" => 'Translated (fallback)',
                        ], $fallback);

                        $translator->addLines([
                            "models.{$model}.name.c1.translated-country-fallback" => 'Translated (country + fallback)',
                        ], $fallback);

                        return $locale;
                    },
                    static function (TestCase $test): Country {
                        $country = Country::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'country name',
                            'code' => 'c1',
                        ]);

                        City::factory()->create([
                            'id'         => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'       => 'No translation',
                            'country_id' => $country->getKey(),
                        ]);
                        City::factory()->create([
                            'id'         => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'name'       => 'translated',
                            'country_id' => $country->getKey(),
                        ]);
                        City::factory()->create([
                            'id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'name'       => 'translated-fallback',
                            'country_id' => $country->getKey(),
                        ]);
                        City::factory()->create([
                            'id'         => '9d5bf4eb-f44e-4c2f-9180-5fc78e75d928',
                            'name'       => 'translated-country',
                            'country_id' => $country->getKey(),
                        ]);
                        City::factory()->create([
                            'id'         => '7d4795e9-c687-4ef8-acd7-017afe63bb50',
                            'name'       => 'translated-country-fallback',
                            'country_id' => $country->getKey(),
                        ]);

                        City::factory()->create([
                            'name'       => 'Another country',
                            'country_id' => Country::factory()->create(),
                        ]);

                        return $country;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
