<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Maatwebsite\Excel\Facades\Excel;
use Tests\DataProviders\TenantDataProvider;
use Tests\ResponseTypes\CsvContentType;
use Tests\TestCase;

use function is_a;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\DownloadController
 */
class DownloadControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::csv
     *
     * @dataProvider dataProviderCsv
     */
    public function testCsv(
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
        $query = /** @lang GraphQL */ <<<'GRAPHQL'
        query assets($first: Int, $page: Int){
          assets(first:$first, page: $page){
            data{
              id
              product{
                name
                sku
              }
            }
          }
        }
        GRAPHQL;

        $input = [
            'operationName' => 'assets',
            'variables'     => [
                'first' => 1,
                'page'  => 1,
            ],
            'query'         => $query,
        ];

        $this->post('/download/csv', $input)->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCsv(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new ArrayDataProvider([
                'user is allowed' => [
                    new Unknown(),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->make([
                            'organization_id' => $organization,
                        ]);
                    },
                ],
            ]),
            new ArrayDataProvider([
                'success' => [
                    new Response(
                        new Ok(),
                        new CsvContentType(),
                    ),
                    static function (TestCase $test): void {
                        Asset::factory()->count(10)->create();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::excel
     *
     * @dataProvider dataProviderExcel
     *
     * @param array<mixed> $input
     */
    public function testExcel(
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
        $query = /** @lang GraphQL */ <<<'GRAPHQL'
        query assets($first: Int, $page: Int){
          assets(first:$first, page: $page){
            data{
              id
              product{
                name
                sku
              }
            }
          }
        }
        GRAPHQL;

        $input = [
            'operationName' => 'assets',
            'variables'     => [
                'first' => 1,
                'page'  => 1,
            ],
            'query'         => $query,
        ];
        Excel::fake();

        $this->post('/download/excel', $input)->assertThat($expected);

        if (is_a($expected, Ok::class)) {
            Excel::assertDownloaded('export.xlsx');
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderExcel(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new ArrayDataProvider([
                'user is allowed' => [
                    new Unknown(),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->make([
                            'organization_id' => $organization,
                        ]);
                    },
                ],
            ]),
            new ArrayDataProvider([
                'success' => [
                    new Ok(),
                    static function (TestCase $test): void {
                        Asset::factory()->create();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>

        // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::pdf
     *
     * @dataProvider dataProviderPdf
     *
     * @param array<mixed> $input
     */
    public function testPdf(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $exportableFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $exported = null;
        if ($exportableFactory) {
            $exported = $exportableFactory($this);
        }

        // Query
        $query = /** @lang GraphQL */ <<<'GRAPHQL'
        query assets($first: Int, $page: Int){
          assets(first:$first, page: $page){
            data{
              id
              product{
                name
                sku
              }
            }
          }
        }
        GRAPHQL;

        $input = [
            'operationName' => 'assets',
            'variables'     => [
                'first' => 1,
                'page'  => 1,
            ],
            'query'         => $query,
        ];
        PDF::fake();

        $this->post('/download/pdf', $input)->assertThat($expected);

        if (is_a($expected, Ok::class)) {
            PDF::assertViewIs('exports.pdf');
            PDF::assertSee($exported->id);
            PDF::assertSee($exported->product->name);
            PDF::assertSee($exported->product->sku);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPdf(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new ArrayDataProvider([
                'user is allowed' => [
                    new Unknown(),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->make([
                            'organization_id' => $organization,
                        ]);
                    },
                ],
            ]),
            new ArrayDataProvider([
                'success' => [
                    new Ok(),
                    static function (TestCase $test): Asset {
                        return Asset::factory()->create();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
