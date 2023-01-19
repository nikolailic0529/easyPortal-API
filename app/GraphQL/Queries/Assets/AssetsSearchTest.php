<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\Models\Asset;
use App\Models\Organization;
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
use Tests\WithSearch;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @coversNothing
 * @internal
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class AssetsSearchTest extends TestCase {
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
        Closure $factory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        if ($factory) {
            $this->makeSearchable($factory($this, $org, $user));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
            assetsSearch(search: "*") {
                id
            }
            assetsSearchAggregated(search: "*") {
                count
            }
        }')->assertThat($expected);
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
                new OrgRootDataProvider('assetsSearch'),
                new OrgUserDataProvider('assetsSearch', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('assetsSearch'),
                        [],
                        static function (TestCase $test, Organization $org): Asset {
                            return Asset::factory()->ownedBy($org)->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('assetsSearch', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
                new OrgUserDataProvider('assetsSearch', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated(
                            'assetsSearch',
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
                        static function (TestCase $test, Organization $org): Asset {
                            return Asset::factory()->ownedBy($org)->create([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
