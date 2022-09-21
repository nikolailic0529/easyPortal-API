<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Distributors;

use App\Models\Distributor;
use App\Models\Organization;
use App\Models\User;
use Closure;
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
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @coversNothing
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class DistributorsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                                              $orgFactory
     * @param UserFactory                                                      $userFactory
     * @param SettingsFactory                                                  $settingsFactory
     * @param Closure(static, ?Organization, ?User): array<string, mixed>|null $whereFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $whereFactory = null,
    ): void {
        // Prepare
        $org   = $this->setOrganization($orgFactory);
        $user  = $this->setUser($userFactory, $org);
        $where = $whereFactory
            ? $whereFactory($this, $org, $user)
            : null;

        $this->setSettings($settingsFactory);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query distributors($where: SearchByConditionDistributorsQuery) {
                    distributors(where: $where) {
                        id
                        name
                    }
                    distributorsAggregated(where: $where) {
                        count
                        groups(groupBy: {name: asc}) {
                            key
                            count
                        }
                        groupsAggregated(groupBy: {name: asc}) {
                            count
                        }
                    }
                }
            ', ['where' => $where])
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
                new OrgRootDataProvider('distributors'),
                new OrgUserDataProvider('distributors', [
                    'assets-view',
                    'contracts-view',
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('distributors'),
                        [],
                        static function (): array {
                            return [];
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('distributors'),
                new OrgUserDataProvider('distributors', [
                    'assets-view',
                    'contracts-view',
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated(
                            'distributors',
                            [
                                [
                                    'id'   => '99a8af42-ff6b-4ed9-938a-d0c81fbac0bc',
                                    'name' => 'Distributor',
                                ],
                            ],
                            [
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'key'   => 'Distributor',
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
                                ],
                            ],
                        ),
                        [
                            // empty
                        ],
                        static function (): array {
                            $distributor = Distributor::factory()->create([
                                'id'   => '99a8af42-ff6b-4ed9-938a-d0c81fbac0bc',
                                'name' => 'Distributor',
                            ]);

                            return [
                                'id' => ['equal' => $distributor->getKey()],
                            ];
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
