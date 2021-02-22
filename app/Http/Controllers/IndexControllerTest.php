<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentTypes\HtmlContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Tests\DataProviders\TenantDataProvider;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\IndexController
 */
class IndexControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::index
     *
     * @dataProvider dataProviderIndex
     *
     * @param array<mixed> $headers
     */
    public function testIndex(Response $expected, Closure $tenantFactory, array $headers = []): void {
        $this->setTenant($tenantFactory);

        $this->get('/', $headers)->assertThat($expected);
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
            new ArrayDataProvider([
                'Accept text/html'        => [
                    new Response(
                        new Ok(),
                        new HtmlContentType(),
                    ),
                    [],
                ],
                'Accept application/json' => [
                    new OkResponse(self::class),
                    [
                        'Accept' => 'application/json',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
