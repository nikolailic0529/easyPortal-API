<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Document;
use App\Models\Type;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Documents\DocumentTypes
 */
class DocumentTypesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $localeFactory = null,
        Closure $typesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setSettings($settings);

        if ($typesFactory) {
            $typesFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                documentTypes(where: {documents: { where: {}, count: {lessThan: 1} }}) {
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
                'id'          => 'fe082ccb-2997-402a-91c8-6669faf4ea5a',
                'name'        => 'Not In quotes config',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Type::factory()->create([
                'name'        => 'Not a document type',
                'object_type' => 'Model',
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
        $a           = [
            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
            'name' => 'Translated (locale)',
        ];
        $b           = [
            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
            'name' => 'Translated (fallback)',
        ];
        $c           = [
            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
            'name' => 'No translation',
        ];
        $d           = [
            'id'   => 'fe082ccb-2997-402a-91c8-6669faf4ea5a',
            'name' => 'Not In quotes config',
        ];

        return (new MergeDataProvider([
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('documentTypes'),
                new OrganizationUserDataProvider('documentTypes', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                        => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$a, $b, $c]),
                        [
                            'ep.quote_types' => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'contract_types match'                     => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$a, $b, $c, $d]),
                        [
                            'ep.contract_types' => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'quote_types + contract_types'             => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$a, $b, $c]),
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                            'ep.quote_types'    => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'quote_types and contract_types not match' => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1650eg',
                            ],
                            'ep.quote_types'    => [
                                'ebf65354-6c4e-42a9-98a7-77ae6b6e3caf',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'no quote_types + no contract_types'       => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                            'ep.quote_types'    => [
                                // empty
                            ],

                        ],
                        $translation,
                        $factory,
                    ],
                ]),
            ),
            'contracts-view' => new CompositeDataProvider(
                new OrganizationDataProvider('documentTypes'),
                new OrganizationUserDataProvider('documentTypes', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                        => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [
                            // empty
                        ]),
                        [
                            'ep.quote_types' => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'contract_types match'                     => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$a, $b, $c]),
                        [
                            'ep.contract_types' => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'quote_types + contract_types'             => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$b]),
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                            'ep.quote_types'    => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'quote_types and contract_types not match' => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1650eg',
                            ],
                            'ep.quote_types'    => [
                                'ebf65354-6c4e-42a9-98a7-77ae6b6e3caf',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'no quote_types + no contract_types'       => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                            'ep.quote_types'    => [
                                // empty
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                ]),
            ),
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('documentTypes'),
                new OrganizationUserDataProvider('documentTypes', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                        => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$a, $b, $c]),
                        [
                            'ep.quote_types' => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'contract_types match'                     => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$d]),
                        [
                            'ep.contract_types' => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'quote_types + contract_types'             => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [$a, $c]),
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ],
                            'ep.quote_types'    => [
                                'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                '6f19ef5f-5963-437e-a798-29296db08d59',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'quote_types and contract_types not match' => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1650eg',
                            ],
                            'ep.quote_types'    => [
                                'ebf65354-6c4e-42a9-98a7-77ae6b6e3caf',
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                    'no quote_types + no contract_types'       => [
                        new GraphQLSuccess('documentTypes', DocumentTypes::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                            'ep.quote_types'    => [
                                // empty
                            ],
                        ],
                        $translation,
                        $factory,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
