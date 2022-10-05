<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Data\Country;
use Closure;
use Illuminate\Translation\Translator;
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
class CountriesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $localeFactory = null,
        Closure $countriesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        if ($countriesFactory) {
            $countriesFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                countries(where: {anyOf: [
                    { assets: { where: {}, count: {lessThan: 1} } }
                    { cities: { where: {}, count: {lessThan: 1} } }
                    { customers: { where: {}, count: {lessThan: 1} } }
                ]}) {
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
            new AuthOrgDataProvider('countries'),
            new AuthMeDataProvider('countries'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('countries', [
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
                            "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
                        ], $locale);

                        $translator->addLines([
                            "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
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
