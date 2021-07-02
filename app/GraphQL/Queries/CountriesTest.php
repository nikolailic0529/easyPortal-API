<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Country;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomerTypes
 */
class CountriesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        Closure $countriesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($countriesFactory) {
            $countriesFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                countries {
                    id
                    name
                    code
                }
            }')
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
            new OrganizationDataProvider('countries'),
            new AuthUserDataProvider('countries'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('countries', self::class, [
                        [
                            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'name' => 'Translated (locale)',
                            'code' => 'c1',
                        ],
                        [
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'name' => 'Translated (fallback)',
                            'code' => 'c2',
                        ],
                        [
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name' => 'No translation',
                            'code' => 'c3',
                        ],
                    ]),
                    static function (TestCase $test): string {
                        $translator = $test->app()->make(Translator::class);
                        $fallback   = $translator->getFallback();
                        $locale     = $test->app()->getLocale();
                        $model      = (new Country())->getMorphClass();

                        $translator->addLines([
                            "models.{$model}.name.c1" => 'Translated (locale)',
                        ], $locale);

                        $translator->addLines([
                            "models.{$model}.name.c2" => 'Translated (fallback)',
                        ], $fallback);

                        return $locale;
                    },
                    static function (TestCase $test): void {
                        Country::factory()->create([
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'code' => 'c3',
                            'name' => 'No translation',
                        ]);
                        Country::factory()->create([
                            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'code' => 'c1',
                            'name' => 'Should be translated',
                        ]);
                        Country::factory()->create([
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'code' => 'c2',
                            'name' => 'Should be translated via fallback',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
