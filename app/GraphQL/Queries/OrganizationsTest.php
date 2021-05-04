<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class OrganizationsTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     *
     * @covers       \App\GraphQL\Queries\OrganizationsConnected::__invoke
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $resellerFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($resellerFactory) {
            $resellerFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                  organizations {
                    id
                    name
                    connected
                  }
                }
            ')->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider('organizations'),
            new RootUserDataProvider('organizations'),
            new ArrayDataProvider([
                'ok'           => [
                    new GraphQLSuccess('organizations', self::class, [
                        [
                            'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                            'name'      => 'Reseller1',
                            'connected' => false,
                        ],
                    ]),
                    static function (): void {
                        Reseller::factory()->create([
                            'id'   => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                            'name' => 'Reseller1',
                        ]);
                    },
                ],
                'ok-connected' => [
                    new GraphQLSuccess('organizations', self::class, [
                        [
                            'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                            'name'      => 'Reseller1',
                            'connected' => true,
                        ],
                    ]),
                    static function (): void {
                        Reseller::factory()->create([
                            'id'   => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                            'name' => 'Reseller1',
                        ]);
                        Organization::factory()->create([
                            'id' => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
