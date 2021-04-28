<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Exports\QueryExport;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Closure;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\InternalServerError;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\UnprocessableEntity;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Maatwebsite\Excel\Facades\Excel;
use Tests\DataProviders\TenantDataProvider;
use Tests\TestCase;

use function is_a;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\ExportController
 */
class ExportControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::csv
     *
     * @dataProvider dataProviderExport
     *
     * @param array<mixed,string> $settings
     *
     * @param array<mixed,string> $variables
     */
    public function testExport(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $exportableFactory = null,
        string $type = 'csv',
        string $query = null,
        array $variables = [
            'first' => 5,
            'page'  => 1,
        ],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));
        $this->setSettings($settings);

        if ($exportableFactory) {
            $exportableFactory($this);
        }

        // Query
        if (!$query) {
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
        }

        $input = [
            'operationName' => 'assets',
            'variables'     => $variables,
            'query'         => $query,
        ];

        if ($type === 'csv' || $type === 'excel') {
            Excel::fake();
        } elseif ($type === 'pdf') {
            PDF::fake();
        } else {
            // empty
        }

        $response = $this->postJson("/download/{$type}", $input);
        if ($expected instanceof Response) {
            $response->assertThat($expected);
            if (is_a($expected, Ok::class)) {
                switch ($type) {
                    case 'csv':
                        Excel::assertDownloaded('export.csv', function (QueryExport $export): bool {
                            $max = $this->app->make(Repository::class)->get('ep.export.max_records');
                            return $export->collection()->count() <= $max;
                        });
                        break;
                    case 'excel':
                        Excel::assertDownloaded('export.xlsx', function (QueryExport $export): bool {
                            $max = $this->app->make(Repository::class)->get('ep.export.max_records');
                            return $export->collection()->count() <= $max;
                        });
                        break;
                    case 'pdf':
                        PDF::assertViewIs('exports.pdf');
                        PDF::assertSee('id');
                        PDF::assertSee('product_name');
                        PDF::assertSee('product_sku');
                        break;

                    default:
                        // empty
                }
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */

    public function dataProviderExport(): array {
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

        $assetFactory = static function (TestCase $test): void {
            Asset::factory()->count(15)->create();
        };
        $settings     = [
            'ep.exports.max_records' => 10,
        ];
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new ArrayDataProvider([
                'guest is not allowed' => [
                    new ExpectedFinal(new Response(
                        new Forbidden(),
                    )),
                    static function (): ?User {
                        return null;
                    },
                ],
                'user is allowed'      => [
                    new Unknown(),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->make([
                            'organization_id' => $organization,
                        ]);
                    },
                ],
            ]),
            new ArrayDataProvider([
                'success-csv'         => [
                    new Ok(),
                    $settings,
                    $assetFactory,
                    'csv',
                    $query,
                ],
                'success-excel'       => [
                    new Ok(),
                    $settings,
                    $assetFactory,
                    'excel',
                    $query,
                ],
                'success-pdf'         => [
                    new Ok(),
                    $settings,
                    $assetFactory,
                    'pdf',
                    $query,
                ],
                'validation-mutation' => [
                    new Response(
                        new UnprocessableEntity(),
                    ),
                    $settings,
                    $assetFactory,
                    'csv',
                    /** @lang GraphQL */ <<<'GRAPHQL'
                        mutation assets($first: Int, $page: Int){
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
                        GRAPHQL,
                ],
                'graphql_error'       => [
                    new Response(
                        new InternalServerError(),
                    ),
                    $settings,
                    $assetFactory,
                    'csv',
                    /** @lang GraphQL */ <<<'GRAPHQL'
                        query assets($first: Int, $page: Int){
                            assets(first:$first, page: $page){
                                data{
                                    idx
                                    product{
                                        name
                                        sku
                                    }
                                }
                            }
                        }
                        GRAPHQL,
                ],
                'without_page'        => [
                    new Response(
                        new UnprocessableEntity(),
                    ),
                    $settings,
                    $assetFactory,
                    'csv',
                    $query,
                    [
                        // Missing page
                        'first' => 5,
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
