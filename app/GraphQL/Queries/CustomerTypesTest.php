<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Type;
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
 * @coversDefaultClass \App\GraphQL\Queries\CustomerTypes
 */
class CustomerTypesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $typesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($typesFactory) {
            $typesFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                customerTypes {
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
                    new GraphQLSuccess('customerTypes', CustomerTypes::class, [
                        'data' => [
                            'customerTypes' => [
                                [
                                    'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                    'name' => 'name aaa',
                                ],
                            ],
                        ],
                    ]),
                    static function (): void {
                        // This should not be returned
                        Type::factory()->create();

                        // This should
                        Type::factory()->create([
                            'id'          => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
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
