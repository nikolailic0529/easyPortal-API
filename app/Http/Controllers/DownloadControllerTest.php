<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Country;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
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
        array $input = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($exportableFactory) {
            $exportableFactory($this);
        }
        $this->postJson('/download', $input)->assertThat($expected);
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
            new AnyDataProvider(),
            new ArrayDataProvider([
                'un paginated export' => [
                    new Response(
                        new Ok(),
                        new CsvContentType(),
                    ),
                    static function (TestCase $test): void {
                        Country::factory()->count(2)->create();
                    },
                    [
                        'operationName' => null,
                        'variables'     => [],
                        'query'         => '{
                            countries {
                                id
                                name
                            }
                        }',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
