<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
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
 * @coversNothing
 */
class ServiceLevelsTest extends TestCase {
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
                serviceLevels(where: {documentEntries: { where: {}, lt: 1 }}) {
                    id
                    oem_id
                    service_group_id
                    sku
                    name
                    description
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
            new OrganizationDataProvider('serviceLevels'),
            new AuthUserDataProvider('serviceLevels'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('serviceLevels', self::class, [
                        [
                            'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                            'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'sku'              => 'SKU#123',
                            'name'             => 'Level',
                            'description'      => 'description',
                        ],
                    ]),
                    static function (): void {
                        $oem   = Oem::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);
                        $group = ServiceGroup::factory()->create([
                            'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'oem_id' => $oem,
                            'sku'    => 'SKU#123',
                            'name'   => 'Group',
                        ]);

                        ServiceLevel::factory()->create([
                            'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                            'oem_id'           => $oem,
                            'service_group_id' => $group,
                            'sku'              => 'SKU#123',
                            'name'             => 'Level',
                            'description'      => 'description',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
