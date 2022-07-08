<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\ServiceGroup;
use App\Models\Type;
use App\Models\User;
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
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Contracts\ContractEntriesAggregated
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ContractEntriesAggregatedTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::prices
     *
     * @dataProvider dataProviderServiceGroups
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param array<mixed>                                         $settings
     * @param Closure(static, ?Organization, ?User): Document|null $factory
     */
    public function testServiceGroups(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $settings = [],
        Closure $factory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settings);

        $id = $factory
            ? $factory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query test($id: ID!) {
                    contract(id: $id) {
                        entriesAggregated {
                            count
                            serviceGroups {
                                count
                                service_group_id
                                serviceGroup {
                                    id
                                    sku
                                    name
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
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
    public function dataProviderServiceGroups(): array {
        $factory  = static function (TestCase $test, Organization $org): Document {
            $type          = Type::factory()->create([
                'id' => 'c0cce0e0-0719-4dbd-ab09-98c0cab8f120',
            ]);
            $reseller      = Reseller::factory()->create([
                'id' => $org,
            ]);
            $serviceGroupA = ServiceGroup::factory()->create([
                'id'   => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                'sku'  => 'SKU#A',
                'name' => 'A',
            ]);
            $serviceGroupB = ServiceGroup::factory()->create([
                'id'   => 'fae217ab-212d-415f-9552-0543c64c6aad',
                'sku'  => 'SKU#B',
                'name' => 'B',
            ]);
            $document      = Document::factory()->create([
                'reseller_id' => $reseller,
                'type_id'     => $type,
            ]);

            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_group_id' => $serviceGroupA,
            ]);
            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_group_id' => $serviceGroupA,
            ]);
            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_group_id' => $serviceGroupB,
            ]);
            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_group_id' => null,
            ]);

            ServiceGroup::factory()->create();

            return $document;
        };
        $settings = [
            'ep.contract_types' => [
                'c0cce0e0-0719-4dbd-ab09-98c0cab8f120',
            ],
        ];
        $expected = [
            'entriesAggregated' => [
                'count'         => 4,
                'serviceGroups' => [
                    [
                        'count'            => 1,
                        'service_group_id' => null,
                        'serviceGroup'     => null,
                    ],
                    [
                        'count'            => 2,
                        'service_group_id' => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                        'serviceGroup'     => [
                            'id'   => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                            'sku'  => 'SKU#A',
                            'name' => 'A',
                        ],
                    ],
                    [
                        'count'            => 1,
                        'service_group_id' => 'fae217ab-212d-415f-9552-0543c64c6aad',
                        'serviceGroup'     => [
                            'id'   => 'fae217ab-212d-415f-9552-0543c64c6aad',
                            'sku'  => 'SKU#B',
                            'name' => 'B',
                        ],
                    ],
                ],
            ],
        ];

        return (new MergeDataProvider([
            'root'           => new CompositeDataProvider(
                new RootOrganizationDataProvider('contract'),
                new OrganizationUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', null, $expected),
                        $settings,
                        $factory,
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('contract'),
                new OrganizationUserDataProvider('contract', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', null, $expected),
                        $settings,
                        $factory,
                    ],
                ]),
            ),
            'organization'   => new CompositeDataProvider(
                new OrganizationDataProvider('contract'),
                new OrganizationUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', null, $expected),
                        $settings,
                        $factory,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
