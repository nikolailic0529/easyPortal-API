<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\Models\Coverage;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class AssetCoveragesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $coverageFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($coverageFactory) {
            $coverageFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                assetCoverages(where: { assets: { where: {}, count: {lessThan: 1} }}) {
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
        $provider = new ArrayDataProvider([
            'ok' => [
                new GraphQLSuccess('assetCoverages', self::class, [
                    [
                        'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'name' => 'coverage1',
                    ],
                ]),
                static function (): void {
                    Coverage::factory()->create([
                        'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'name' => 'coverage1',
                    ]);
                },
            ],
        ]);

        return (new MergeDataProvider([
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('assetCoverages'),
                new OrganizationUserDataProvider('assetCoverages', [
                    'customers-view',
                ]),
                $provider,
            ),
            'assets-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('assetCoverages'),
                new OrganizationUserDataProvider('assetCoverages', [
                    'assets-view',
                ]),
                $provider,
            ),
        ]))->getData();
    }
    // </editor-fold>
}
