<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Oem;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class OemsTest extends TestCase {
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
        Closure $oemsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($oemsFactory) {
            $oemsFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                oems(where: {anyOf: [
                    { assets: { where: {}, count: {lessThan: 1} } }
                    { documents: { where: {}, count: {lessThan: 1} } }
                ]}) {
                    id
                    key
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
            new OrganizationDataProvider('oems'),
            new UserDataProvider('oems'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('oems', self::class, [
                        [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'key'  => 'abr',
                            'name' => 'oem1',
                        ],
                    ]),
                    static function (): void {
                        Oem::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'key'  => 'abr',
                            'name' => 'oem1',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}