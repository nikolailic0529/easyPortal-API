<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

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
 * @covers \App\GraphQL\Queries\Contracts\ContractsSearch
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class ContractsSearchTest extends TestCase {
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
        Closure $contractsFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        if ($contractsFactory) {
            $this->makeSearchable($contractsFactory($this, $org, $user));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    contractsSearch(search: "*") {
                        id
                    }
                    contractsSearchAggregated(search: "*") {
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
                new OrgRootDataProvider('contractsSearch'),
                new OrgUserDataProvider('contractsSearch', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('contractsSearch'),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'type_id'     => Type::factory()->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                ]),
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('contractsSearch', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('contractsSearch', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok'                  => [
                        new GraphQLPaginated(
                            'contractsSearch',
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
                                    'is_contract' => true,
                                    'is_quote'    => false,
                                ]),
                                Document::factory()->create([
                                    'type_id'     => Type::factory()->create(),
                                    'is_hidden'   => false,
                                    'is_contract' => true,
                                    'is_quote'    => false,
                                ]),
                            ]);
                        },
                    ],
                    'is_document = false' => [
                        new GraphQLPaginated('contractsSearch', [], [
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
                    'is_quote = true'     => [
                        new GraphQLPaginated('contractsSearch', [], [
                            'count' => 0,
                        ]),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => false,
                                'is_contract' => false,
                                'is_quote'    => true,
                            ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
