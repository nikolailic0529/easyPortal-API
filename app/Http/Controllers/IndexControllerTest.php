<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Services\Maintenance\Storage;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentTypes\HtmlContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Tests\DataProviders\Http\Organizations\AnyOrganizationDataProvider;
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
     * @param array<mixed> $data
     */
    public function testIndex(
        Response $expected,
        Closure $organizationFactory,
        array $headers = [],
        array $data = null,
    ): void {
        $this->setOrganization($organizationFactory);

        if ($data) {
            $this->app->make(Storage::class)->save($data);
        }

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
            new AnyOrganizationDataProvider(),
            new ArrayDataProvider([
                'Accept text/html'                      => [
                    new Response(
                        new Ok(),
                        new HtmlContentType(),
                    ),
                    [],
                ],
                'Accept application/json'               => [
                    new OkResponse(self::class),
                    [
                        'Accept' => 'application/json',
                    ],
                ],
                'Accept application/json (maintenance)' => [
                    new OkResponse(self::class),
                    [
                        'Accept' => 'application/json',
                    ],
                    [
                        'enabled' => true,
                        'message' => 'abc',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
