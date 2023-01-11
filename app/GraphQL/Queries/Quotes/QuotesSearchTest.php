<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Data\Type;
use App\Models\Document;
use App\Models\Organization;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSearch;
use Tests\WithSettings;
use Tests\WithUser;

use function count;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Quotes\QuotesSearch
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class QuotesSearchTest extends TestCase {
    use WithSearch;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $quotesFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($settingsFactory) {
            $this->setSettings($settingsFactory);
        }

        if ($quotesFactory) {
            $this->makeSearchable($quotesFactory($this, $org, $user));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    quotesSearch(search: "*") {
                        id
                    }
                    quotesSearchAggregated(search: "*") {
                        count
                    }
                }
            ')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        $factory = static function (TestCase $test, Organization $org): Collection {
            return new Collection([
                Document::factory()->ownedBy($org)->create([
                    'id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'type_id' => Type::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                    ]),
                ]),
                Document::factory()->ownedBy($org)->create([
                    'id'      => 'af96eb34-def6-40e1-b346-ca449017f393',
                    'type_id' => Type::factory()->create([
                        'id' => 'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                    ]),
                ]),
            ]);
        };
        $objects = [
            [
                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            ],
        ];

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('quotesSearch'),
                new OrgUserDataProvider('quotesSearch', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('quotesSearch'),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'type_id' => Type::factory()->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                ]),
                            ]);
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('quotesSearch', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('quotesSearch', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                         => [
                        new GraphQLPaginated('quotesSearch', $objects, [
                            'count' => count($objects),
                        ]),
                        [
                            'ep.document_statuses_hidden' => [
                                '12dcd0c7-2fac-4140-8808-4a72aa8600ab',
                            ],
                            'ep.quote_types'              => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'no quote_types + contract_types not match' => [
                        new GraphQLPaginated('quotesSearch', $objects, [
                            'count' => count($objects),
                        ]),
                        [
                            'ep.document_statuses_hidden' => [
                                '12dcd0c7-2fac-4140-8808-4a72aa8600ab',
                            ],
                            'ep.contract_types'           => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                            'ep.quote_types'              => [
                                // empty
                            ],
                        ],
                        $factory,
                    ],
                    'no quote_types + contract_types match'     => [
                        new GraphQLPaginated(
                            'quotesSearch',
                            [
                                [
                                    'id' => '2bf6d64b-df97-401c-9abd-dc2dd747e2b0',
                                ],
                            ],
                            [
                                'count' => 1,
                            ],
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                            'ep.quote_types'              => [
                                // empty
                            ],
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'id' => '2bf6d64b-df97-401c-9abd-dc2dd747e2b0',
                            ]);
                        },
                    ],
                    'quote_types not match'                     => [
                        new GraphQLPaginated('quotesSearch', [], [
                            'count' => 0,
                        ]),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create();
                        },
                    ],
                    'no quote_types + no contract_types'        => [
                        new GraphQLPaginated('quotesSearch', [], [
                            'count' => 0,
                        ]),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [],
                            'ep.quote_types'              => [],
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create();
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
