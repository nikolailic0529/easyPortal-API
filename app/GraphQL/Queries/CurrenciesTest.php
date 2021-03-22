<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
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
 */
class CurrenciesTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $currenciesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($currenciesFactory) {
            $currenciesFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                currencies {
                    id
                    name
                    code
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
                    new GraphQLSuccess('currencies', self::class, [
                        [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'currency1',
                            'code' => 'CUR',
                        ],
                    ]),
                    static function (): void {
                        Currency::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'currency1',
                            'code' => 'CUR',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
