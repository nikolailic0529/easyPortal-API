<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\ContractsAggregate
 */
class ContractsAggregateTest extends TestCase {
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderQuery
     *
     * @param array<mixed> $settings
     * @param array{where: array<mixed>} $params
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $factory = null,
        array $params = [],
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($settings) {
            $this->setSettings($settings);
        }

        if ($factory) {
            $factory($this, $organization, $user);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */ <<<'GRAPHQL'
                query ($where: SearchByConditionDocumentsQuery) {
                    contractsAggregate(where: $where) {
                        count
                        prices {
                            count
                            amount
                            currency_id
                            currency {
                                id
                                name
                                code
                            }
                        }
                    }
                }
                GRAPHQL,
                $params + [
                    'where' => [],
                ],
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        $params  = [
            'where' => [
                'price' => ['lte' => 100],
            ],
        ];
        $factory = static function (TestCase $test, Organization $organization): void {
            // Type
            $type = Type::factory()->create([
                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
            ]);

            // Resellers
            $resellerA = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $resellerB = Reseller::factory()->create();

            // Currencies
            $currencyA = Currency::factory()->create([
                'id'   => 'fd6be569-3b51-4c8c-a132-3b57b1b8624a',
                'code' => 'A',
                'name' => 'A',
            ]);
            $currencyB = Currency::factory()->create([
                'id'   => '8457ba3b-defd-4442-a8ab-125f3ad89fa9',
                'code' => 'B',
                'name' => 'B',
            ]);

            // Documents
            Document::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $resellerA,
                'currency_id' => $currencyA,
                'price'       => 10,
            ]);
            Document::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $resellerB,
                'currency_id' => $currencyA,
                'price'       => 15,
            ]);
            Document::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $resellerB,
                'currency_id' => $currencyB,
                'price'       => 10,
            ]);
            Document::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $resellerB,
                'currency_id' => null,
                'price'       => 10,
            ]);

            // Wrong price
            Document::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $resellerA,
                'currency_id' => $currencyA,
                'price'       => 1000,
            ]);

            // Wrong type
            Document::factory()->create([
                'reseller_id' => $resellerA,
                'currency_id' => $currencyA,
                'price'       => 10,
            ]);
        };

        return (new MergeDataProvider([
            'root'           => new CompositeDataProvider(
                new RootOrganizationDataProvider('contractsAggregate'),
                new OrganizationUserDataProvider('contractsAggregate', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contractsAggregate', self::class, [
                            'count'  => 4,
                            'prices' => [
                                [
                                    'count'       => 1,
                                    'amount'      => 10,
                                    'currency_id' => null,
                                    'currency'    => null,
                                ],
                                [
                                    'count'       => 1,
                                    'amount'      => 10,
                                    'currency_id' => '8457ba3b-defd-4442-a8ab-125f3ad89fa9',
                                    'currency'    => [
                                        'id'   => '8457ba3b-defd-4442-a8ab-125f3ad89fa9',
                                        'name' => 'B',
                                        'code' => 'B',
                                    ],
                                ],
                                [
                                    'count'       => 2,
                                    'amount'      => 25,
                                    'currency_id' => 'fd6be569-3b51-4c8c-a132-3b57b1b8624a',
                                    'currency'    => [
                                        'id'   => 'fd6be569-3b51-4c8c-a132-3b57b1b8624a',
                                        'name' => 'A',
                                        'code' => 'A',
                                    ],
                                ],
                            ],
                        ]),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('contractsAggregate'),
                new OrganizationUserDataProvider('contractsAggregate', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contractsAggregate', self::class, [
                            'count'  => 1,
                            'prices' => [
                                [
                                    'count'       => 1,
                                    'amount'      => 10,
                                    'currency_id' => 'fd6be569-3b51-4c8c-a132-3b57b1b8624a',
                                    'currency'    => [
                                        'id'   => 'fd6be569-3b51-4c8c-a132-3b57b1b8624a',
                                        'name' => 'A',
                                        'code' => 'A',
                                    ],
                                ],
                            ],
                        ]),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'organization'   => new CompositeDataProvider(
                new OrganizationDataProvider('contractsAggregate', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrganizationUserDataProvider('contractsAggregate', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess('contractsAggregate', self::class, [
                            'count'  => 1,
                            'prices' => [
                                [
                                    'count'       => 1,
                                    'amount'      => 10,
                                    'currency_id' => 'fd6be569-3b51-4c8c-a132-3b57b1b8624a',
                                    'currency'    => [
                                        'id'   => 'fd6be569-3b51-4c8c-a132-3b57b1b8624a',
                                        'name' => 'A',
                                        'code' => 'A',
                                    ],
                                ],
                            ],
                        ]),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                        $params,
                    ],
                    'no types'       => [
                        new GraphQLSuccess('contractsAggregate', self::class, [
                            'count'  => 0,
                            'prices' => [],
                        ]),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                        ],
                        $factory,
                        $params,
                    ],
                    'type not match' => [
                        new GraphQLSuccess('contractsAggregate', self::class, [
                            'count'  => 0,
                            'prices' => [],
                        ]),
                        [
                            'ep.contract_types' => [
                                'da436d68-a6b5-424e-a25e-8394b697d191',
                            ],
                        ],
                        $factory,
                        $params,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
