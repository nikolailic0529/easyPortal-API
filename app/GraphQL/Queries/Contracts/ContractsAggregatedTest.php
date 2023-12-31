<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Customer;
use App\Models\Data\Currency;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Contracts\ContractsAggregated
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class ContractsAggregatedTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory         $orgFactory
     * @param UserFactory                 $userFactory
     * @param SettingsFactory             $settingsFactory
     * @param array{where?: array<mixed>} $params
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $factory = null,
        array $params = [],
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($settingsFactory) {
            $this->setSettings($settingsFactory);
        }

        if ($factory) {
            $factory($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query ($where: SearchByConditionDocumentsQuery) {
                    contractsAggregated(where: $where) {
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
                'anyOf' => [
                    [
                        'currency_id' => [
                            'notEqual' => '920cc290-1cc9-484a-b4c5-7d4e74299811',
                        ],
                    ],
                    [
                        'currency_id' => [
                            'isNull' => 'yes',
                        ],
                    ],
                ],
            ],
        ];
        $factory = static function (TestCase $test, Organization $org): void {
            // Resellers
            $resellerA = Reseller::factory()->create([
                'id' => $org->getKey(),
            ]);
            $resellerB = Reseller::factory()->create();

            // Customers
            $customerA = Customer::factory()->create([
                'id' => $org->getKey(),
            ]);
            $customerB = Customer::factory()->create();

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
            $currencyC = Currency::factory()->create([
                'id'   => '920cc290-1cc9-484a-b4c5-7d4e74299811',
                'code' => 'C',
                'name' => 'C',
            ]);

            // Documents
            Document::factory()->create([
                'reseller_id' => $resellerA,
                'customer_id' => $customerA,
                'currency_id' => $currencyA,
                'price'       => 10,
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => false,
            ]);
            Document::factory()->create([
                'reseller_id' => $resellerB,
                'customer_id' => $customerB,
                'currency_id' => $currencyA,
                'price'       => 15,
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => false,
            ]);
            Document::factory()->create([
                'reseller_id' => $resellerB,
                'customer_id' => $customerB,
                'currency_id' => $currencyB,
                'price'       => 10,
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => false,
            ]);
            Document::factory()->create([
                'reseller_id' => $resellerB,
                'customer_id' => $customerB,
                'currency_id' => null,
                'price'       => 10,
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => false,
            ]);

            // Wrong price
            Document::factory()->create([
                'reseller_id' => $resellerA,
                'customer_id' => $customerA,
                'currency_id' => $currencyC,
                'price'       => 1000,
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => false,
            ]);

            // Quote
            Document::factory()->create([
                'reseller_id' => $resellerA,
                'customer_id' => $customerA,
                'currency_id' => $currencyA,
                'price'       => 10,
                'is_hidden'   => false,
                'is_contract' => false,
                'is_quote'    => true,
            ]);

            // Hidden
            Document::factory()->create([
                'reseller_id' => $resellerA,
                'currency_id' => $currencyA,
                'price'       => 5,
                'is_hidden'   => true,
                'is_contract' => true,
                'is_quote'    => false,
            ]);
        };

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('contractsAggregated'),
                new OrgUserDataProvider('contractsAggregated', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contractsAggregated', [
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
                            // empty
                        ],
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('contractsAggregated', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('contractsAggregated', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contractsAggregated', [
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
                            // empty
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
