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

        $this->setSettings($settingsFactory);

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
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'type_id'     => Type::factory()->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                ]),
                                'is_hidden'   => false,
                                'is_contract' => false,
                                'is_quote'    => true,
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
                    'ok'                  => [
                        new GraphQLPaginated(
                            'quotesSearch',
                            [
                                [
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                ],
                            ],
                            [
                                'count' => 1,
                            ],
                        ),
                        [
                            // empty
                        ],
                        static function (TestCase $test, ?Organization $org): Collection {
                            return new Collection([
                                Document::factory()->ownedBy($org)->create([
                                    'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'type_id'     => Type::factory()->create([
                                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    ]),
                                    'is_hidden'   => false,
                                    'is_contract' => false,
                                    'is_quote'    => true,
                                ]),
                                Document::factory()->create([
                                    'type_id'     => Type::factory()->create(),
                                    'is_hidden'   => false,
                                    'is_contract' => false,
                                    'is_quote'    => true,
                                ]),
                            ]);
                        },
                    ],
                    'is_document = false' => [
                        new GraphQLPaginated('quotesSearch', [], [
                            'count' => 0,
                        ]),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => false,
                                'is_contract' => false,
                                'is_quote'    => false,
                            ]);
                        },
                    ],
                    'is_contract = true'  => [
                        new GraphQLPaginated('quotesSearch', [], [
                            'count' => 0,
                        ]),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
