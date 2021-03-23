<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class CustomersTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($customerFactory) {
            $customerFactory($this);
        }

        // Test
        $this
        ->graphQL(/** @lang GraphQL */ '{
            customers {
                data {
                    id
                    name
                    assets_count
                    contacts_count
                    locations_count
                    locations {
                        id
                        state
                        postcode
                        line_one
                        line_two
                        lat
                        lng
                    }
                    contacts {
                        name
                        email
                        phone_valid
                    }
                },
                paginatorInfo {
                    count
                    currentPage
                    firstItem
                    hasMorePages
                    lastItem
                    lastPage
                    perPage
                    total
                }
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
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new UserDataProvider('customers'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLPaginated('customers', self::class, [
                        [
                            'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'            => 'name aaa',
                            'assets_count'    => 0,
                            'locations_count' => 1,
                            'locations'       => [
                                [
                                    'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                    'state'    => 'state1',
                                    'postcode' => '19911',
                                    'line_one' => 'line_one_data',
                                    'line_two' => 'line_two_data',
                                    'lat'      => '47.91634204',
                                    'lng'      => '-2.26318359',
                                ],
                            ],
                            'contacts_count'  => 1,
                            'contacts'        => [
                                [
                                    'name'        => 'contact1',
                                    'email'       => 'contact1@test.com',
                                    'phone_valid' => false,
                                ],
                            ],
                        ],
                    ]),
                    static function (): void {
                        Customer::factory()
                            ->hasLocations(1, [
                                'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'    => 'state1',
                                'postcode' => '19911',
                                'line_one' => 'line_one_data',
                                'line_two' => 'line_two_data',
                                'lat'      => '47.91634204',
                                'lng'      => '-2.26318359',
                            ])
                            ->hasContacts(1, [
                                'name'        => 'contact1',
                                'email'       => 'contact1@test.com',
                                'phone_valid' => false,
                            ])
                            ->create([
                                'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name'            => 'name aaa',
                                'assets_count'    => 0,
                                'contacts_count'  => 1,
                                'locations_count' => 1,
                            ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
