<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Customer;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\ResponseTypes\CsvContentType;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\DownloadController
 */
class DownloadControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::index
     *
     * @dataProvider dataProviderIndex
     *
     * @param array<mixed> $input
     */
    public function testIndex(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $exportableFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($exportableFactory) {
            $exportableFactory($this);
        }

        // Query
        $input = [
            'operationName' => null,
            'variables'     => [],
            'query'         => '{
                allCustomers {
                    id
                    name
                    type_id
                    status_id
                    assets_count
                    contacts_count
                    locations_count
                }
            }',
        ];

        $this->post('/download', $input)->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderIndex(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new UserDataProvider('allCustomers'),
            new ArrayDataProvider([
                'success' => [
                    new Response(
                        new Ok(),
                        new CsvContentType(),
                    ),
                    static function (TestCase $test): void {
                        Customer::factory()->count(2)->create();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
