<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Exports\QueryExport;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Reseller;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\InternalServerError;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\UnprocessableEntity;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Maatwebsite\Excel\Facades\Excel;
use Tests\DataProviders\Http\Organizations\OrganizationDataProvider;
use Tests\DataProviders\Http\Users\UserDataProvider;
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
     * @param array<mixed,string> $variables
     */
    public function testExport(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $exportableFactory = null,
        string $type = 'csv',
        string $query = null,
        array $variables = [
            'first' => 5,
            'page'  => 1,
        ],
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $exportedCount = 0;
        if ($exportableFactory) {
            $exportedCount = $exportableFactory($this, $organization, $user);
        }

        // Query
        if (!$query) {
            $query = /** @lang GraphQL */
                <<<'GRAPHQL'
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
        $response->assertThat($expected);
        if (is_a($expected, Ok::class)) {
            switch ($type) {
                case 'csv':
                    Excel::assertDownloaded(
                        'export.csv',
                        static function (QueryExport $export) use ($exportedCount): bool {
                            return $export->collection()->count() === $exportedCount;
                        },
                    );
                    break;
                case 'excel':
                    Excel::assertDownloaded(
                        'export.xlsx',
                        static function (QueryExport $export) use ($exportedCount): bool {
                            return $export->collection()->count() === $exportedCount;
                        },
                    );
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
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */

    public function dataProviderExport(): array {
        $query = /** @lang GraphQL */
            <<<'GRAPHQL'
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

        $assetFactory = static function (TestCase $test, Organization $organization): int {
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            Asset::factory()->for($reseller)->count(15)->create();

            // Data + Header
            return 16;
        };

        return (new CompositeDataProvider(
            new OrganizationDataProvider(),
            new UserDataProvider([
                'view-assets',
            ]),
            new ArrayDataProvider([
                'success-csv'         => [
                    new Ok(),
                    $assetFactory,
                    'csv',
                    $query,
                ],
                'success-excel'       => [
                    new Ok(),
                    $assetFactory,
                    'excel',
                    $query,
                ],
                'success-pdf'         => [
                    new Ok(),
                    $assetFactory,
                    'pdf',
                    $query,
                ],
                'filters-csv'         => [
                    new Ok(),
                    static function (TestCase $test, Organization $organization): int {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        Asset::factory()->for($reseller)->count(14)->create();
                        Asset::factory()->for($reseller)->count(1)->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                        ]);

                        // Data + Header
                        return 2;
                    },
                    'csv',
                    /** @lang GraphQL */ <<<'GRAPHQL'
                    query assets(
                        $first: Int,
                        $page: Int,
                        $where:SearchByConditionAssetsQuery,
                        $order:[SortByClauseAssetsSort!]
                    ){
                        assets(first:$first, page: $page, where:$where, order: $order){
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
                    [
                        'first' => 5,
                        'page'  => 1,
                        'where' => [
                            'id' => [
                                'in' => [
                                    'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                ],
                            ],
                        ],
                    ],
                ],
                'validation-mutation' => [
                    new Response(
                        new UnprocessableEntity(),
                    ),
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
                    $assetFactory,
                    'csv',
                    $query,
                    [
                        // Missing page
                        'first' => 5,
                    ],
                ],
                'different-reseller'  => [
                    new Ok(),
                    static function (TestCase $test, Organization $organization): int {
                        Asset::factory()->count(15)->create()->count();

                        return 0;
                    },
                    'csv',
                    $query,
                    [
                        'first' => 5,
                        'page'  => 1,
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
