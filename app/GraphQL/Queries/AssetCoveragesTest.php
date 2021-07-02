<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\AssetCoverage;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class AssetCoveragesTest extends TestCase {
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
                assetCoverages {
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
        return (new CompositeDataProvider(
            new OrganizationDataProvider('assetCoverages'),
            new AuthUserDataProvider('assetCoverages'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('assetCoverages', self::class, [
                        [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'coverage1',
                        ],
                    ]),
                    static function (): void {
                        AssetCoverage::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'coverage1',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
