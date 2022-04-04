<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Currency;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class CurrenciesTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        Closure $currenciesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($currenciesFactory) {
            $currenciesFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                currencies(where: {documents: { where: {}, count: {lessThan: 1} }}) {
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
            new AnyOrganizationDataProvider('currencies'),
            new UserDataProvider('currencies'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('currencies', self::class, [
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
                        $model      = (new Currency())->getMorphClass();

                        $translator->addLines([
                            "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
                        ], $locale);

                        $translator->addLines([
                            "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
                        ], $fallback);

                        return $locale;
                    },
                    static function (): void {
                        Currency::factory()->create([
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'code' => 'c3',
                            'name' => 'No translation',
                        ]);
                        Currency::factory()->create([
                            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'code' => 'c1',
                            'name' => 'Should be translated',
                        ]);
                        Currency::factory()->create([
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
