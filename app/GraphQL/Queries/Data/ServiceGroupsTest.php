<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Oem;
use App\Models\ServiceGroup;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ServiceGroupsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $factory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($factory) {
            $factory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                serviceGroups(where: {documentEntries: { where: {}, count: {lessThan: 1} }}) {
                    id
                    oem_id
                    sku
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
            new AnyOrganizationDataProvider('serviceGroups'),
            new UserDataProvider('serviceGroups'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('serviceGroups', self::class, [
                        [
                            'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'sku'    => 'SKU#123',
                            'name'   => 'Group',
                        ],
                    ]),
                    static function (): void {
                        $oem = Oem::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);

                        ServiceGroup::factory()->create([
                            'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'oem_id' => $oem,
                            'sku'    => 'SKU#123',
                            'name'   => 'Group',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
