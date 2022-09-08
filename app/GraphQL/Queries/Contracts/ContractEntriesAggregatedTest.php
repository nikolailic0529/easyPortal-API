<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Organization;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Type;
use App\Models\User;
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
 * @deprecated Please use `groups` query instead.
 *
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Contracts\ContractEntriesAggregated
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
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
     * @param Closure(static, ?Organization, ?User): Document|null $factory
     * @param SettingsFactory                                      $settings
     */
    public function testServiceGroups(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settings = null,
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
                            groups(groupBy: {service_group_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {service_group_id: asc}) {
                                count
                            }
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

    /**
     * @covers ::prices
     *
     * @dataProvider dataProviderServiceLevels
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param Closure(static, ?Organization, ?User): Document|null $factory
     * @param array<mixed>                                         $settings
     */
    public function testServiceLevels(
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
                            groups(groupBy: {service_level_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {service_level_id: asc}) {
                                count
                            }
                            serviceLevels {
                                count
                                service_level_id
                                serviceLevel {
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
            $document      = Document::factory()->ownedBy($org)->create([
                'type_id' => $type,
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
                'count'            => 4,
                'groups'           => [
                    [
                        'key'   => null,
                        'count' => 1,
                    ],
                    [
                        'key'   => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                        'count' => 2,
                    ],
                    [
                        'key'   => 'fae217ab-212d-415f-9552-0543c64c6aad',
                        'count' => 1,
                    ],
                ],
                'groupsAggregated' => [
                    'count' => 3,
                ],
                'serviceGroups'    => [
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
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', $expected),
                        $settings,
                        $factory,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', $expected),
                        $settings,
                        $factory,
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderServiceLevels(): array {
        $factory  = static function (TestCase $test, Organization $org): Document {
            $type          = Type::factory()->create([
                'id' => 'c0cce0e0-0719-4dbd-ab09-98c0cab8f120',
            ]);
            $serviceLevelA = ServiceLevel::factory()->create([
                'id'   => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                'sku'  => 'SKU#A',
                'name' => 'A',
            ]);
            $serviceLevelB = ServiceLevel::factory()->create([
                'id'   => 'fae217ab-212d-415f-9552-0543c64c6aad',
                'sku'  => 'SKU#B',
                'name' => 'B',
            ]);
            $document      = Document::factory()->ownedBy($org)->create([
                'type_id' => $type,
            ]);

            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_level_id' => $serviceLevelA,
            ]);
            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_level_id' => $serviceLevelA,
            ]);
            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_level_id' => $serviceLevelB,
            ]);
            DocumentEntry::factory()->create([
                'document_id'      => $document,
                'service_level_id' => null,
            ]);

            ServiceLevel::factory()->create();

            return $document;
        };
        $settings = [
            'ep.contract_types' => [
                'c0cce0e0-0719-4dbd-ab09-98c0cab8f120',
            ],
        ];
        $expected = [
            'entriesAggregated' => [
                'count'            => 4,
                'groups'           => [
                    [
                        'key'   => null,
                        'count' => 1,
                    ],
                    [
                        'key'   => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                        'count' => 2,
                    ],
                    [
                        'key'   => 'fae217ab-212d-415f-9552-0543c64c6aad',
                        'count' => 1,
                    ],
                ],
                'groupsAggregated' => [
                    'count' => 3,
                ],
                'serviceLevels'    => [
                    [
                        'count'            => 1,
                        'service_level_id' => null,
                        'serviceLevel'     => null,
                    ],
                    [
                        'count'            => 2,
                        'service_level_id' => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                        'serviceLevel'     => [
                            'id'   => 'b3fb4b06-e10e-4075-9ab2-4ccf83ae9536',
                            'sku'  => 'SKU#A',
                            'name' => 'A',
                        ],
                    ],
                    [
                        'count'            => 1,
                        'service_level_id' => 'fae217ab-212d-415f-9552-0543c64c6aad',
                        'serviceLevel'     => [
                            'id'   => 'fae217ab-212d-415f-9552-0543c64c6aad',
                            'sku'  => 'SKU#B',
                            'name' => 'B',
                        ],
                    ],
                ],
            ],
        ];

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', $expected),
                        $settings,
                        $factory,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', $expected),
                        $settings,
                        $factory,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
