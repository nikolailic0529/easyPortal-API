<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\City;
use App\Models\Country;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class CitiesTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        Closure $countryFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

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
                    cities(where:{ country_id: { equal: $country_id } }) {
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
            $this->assertCount(4, $this->getQueryLog());
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
            new OrganizationDataProvider('cities'),
            new UserDataProvider('cities'),
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
                            "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
                        ], $locale);

                        $translator->addLines([
                            "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
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
