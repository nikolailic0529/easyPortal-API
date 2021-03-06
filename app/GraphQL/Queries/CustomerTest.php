<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomerTypes
 */
class CustomerTest extends TestCase {
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
        $customer_id = 'wrong';
        if ($customerFactory) {
            $customer_id = $customerFactory($this)->id;
        } else {
            // code here
        }
        // Test
        $query = sprintf('{
            customer(id:"%s") {
                id
                name
                locations_count
                contacts_count
                locations {
                    state
                    postcode
                }
            }
        }', $customer_id);
        $this->graphQL(/** @lang GraphQL */ $query)->assertThat($expected);
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
            new UserDataProvider('customer'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('customer', self::class, [
                        'data' => [
                            'customer' => [
                                'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name'            => 'name aaa',
                                'locations_count' => 1,
                                'contacts_count'  => null,
                                'locations'       => [
                                    [
                                        'state'    => 'state1',
                                        'postcode' => '19911',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    static function (): Customer {
                        // This should not be returned
                        // Customer::factory()->create();

                        // This should
                        $customer = Customer::factory()
                        ->hasLocations(1, [
                            'state'    => 'state1',
                            'postcode' => '19911',
                        ])
                        ->create([
                            'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'            => 'name aaa',
                            'locations_count' => 1,
                        ]);
                        return $customer;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
