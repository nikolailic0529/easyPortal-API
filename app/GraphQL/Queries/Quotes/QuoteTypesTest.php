<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Data\Type;
use App\Models\Document;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Quotes\QuoteTypes
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class QuoteTypesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $localeFactory = null,
        Closure $typesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setSettings($settingsFactory);

        if ($typesFactory) {
            $typesFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                quoteTypes(where: {quotes: { where: {}, count: {lessThan: 1} }}) {
                    id
                    name
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
        $factory     = static function (): void {
            Type::factory()->create([
                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                'name'        => 'No translation',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Type::factory()->create([
                'id'          => '6f19ef5f-5963-437e-a798-29296db08d59',
                'name'        => 'Should be translated',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Type::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'name'        => 'Should be translated via fallback',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Type::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                'name'        => 'Not In quotes config',
                'object_type' => (new Document())->getMorphClass(),
            ]);
        };
        $translation = static function (TestCase $test): string {
            $translator = $test->app()->make(Translator::class);
            $fallback   = $translator->getFallback();
            $locale     = $test->app()->getLocale();
            $model      = (new Type())->getMorphClass();

            $translator->addLines([
                "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
            ], $locale);

            $translator->addLines([
                "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
            ], $fallback);

            return $locale;
        };
        $objects     = [
            [
                'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                'name' => 'Translated (locale)',
            ],
            [
                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'name' => 'Translated (fallback)',
            ],
            [
                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                'name' => 'No translation',
            ],
        ];
        $provider    = new ArrayDataProvider([
            'quote_types match'                  => [
                new GraphQLSuccess('quoteTypes', $objects),
                [
                    'ep.document_statuses_hidden' => [],
                    'ep.quote_types'              => [
                        'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        '6f19ef5f-5963-437e-a798-29296db08d59',
                        'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    ],
                ],
                $translation,
                $factory,
            ],
            'no quote_types + contract_types'    => [
                new GraphQLSuccess('quoteTypes', $objects),
                [
                    'ep.contract_types' => [
                        'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                    ],
                    'ep.quote_types'    => [
                        // empty
                    ],
                ],
                $translation,
                $factory,
            ],
            'quote_types not match'              => [
                new GraphQLSuccess('quoteTypes', [
                    // empty
                ]),
                [
                    'ep.document_statuses_hidden' => [],
                    'ep.quote_types'              => [
                        'f3cb1fac-b454-4f23-bbb4-f3d84a1650eg',
                    ],
                ],
                $translation,
                $factory,
            ],
            'no quote_types + no contract_types' => [
                new GraphQLSuccess('quoteTypes', [
                    // empty
                ]),
                [
                    'ep.document_statuses_hidden' => [],
                    'ep.contract_types'           => [],
                    'ep.quote_types'              => [],
                ],
                $translation,
                $factory,
            ],
        ]);

        return (new MergeDataProvider([
            'quotes-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('quoteTypes'),
                new OrgUserDataProvider('quoteTypes', [
                    'quotes-view',
                ]),
                $provider,
            ),
        ]))->getData();
    }
    // </editor-fold>
}
