<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Status;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomerStatuses
 */
class CustomerStatusesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $statusesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($statusesFactory) {
            $statusesFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                customerStatuses {
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
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('customerStatuses', CustomerStatuses::class, [
                        [
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name' => 'name aaa',
                        ],
                    ]),
                    static function (): void {
                        // This should not be returned
                        Status::factory()->create();

                        // This should
                        Status::factory()->create([
                            'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'        => 'name aaa',
                            'object_type' => (new Customer())->getMorphClass(),
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
