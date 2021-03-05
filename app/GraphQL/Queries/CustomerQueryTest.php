<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Location;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use stdClass;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomerTypes
 */
class CustomerQueryTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $this->setTenant($tenantFactory);
        $this->setUser($userFactory);

        if ($customerFactory) {
            $customerFactory($this);
        }
        // Test 
        $this
            ->graphQL(/** @lang GraphQL */ '{
                customer(id: "f9396bc1-2f2f-4c57-bb8d-7a224ac20944") {
                    id
                    name
                    locations_count
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
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('customer', CustomerQuery::class , [
                        'data' => [
                            'customer' => [
                                'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name'            => 'name aaa',
                                'locations_count' => 2
                            ],
                        ],
                    ]),
                    static function (): void {
                        // This should not be returned
                        Customer::factory()->create();

                        // This should
                        $customer = Customer::factory()->create([
                            'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'        => 'name aaa',
                        ]);
                        $customer->locations = Location::factory()->count(2)->create([ 
                            'object_type' => 'app/customer',
                            'object_id'   => $customer->id,
                        ]);
                        $customer->save();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
