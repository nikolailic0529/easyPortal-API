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
use Tests\DataProviders\Http\Users\OrganizationUserDataProvider;
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
        string $operationName = 'assets',
        string $query = null,
        array $variables = [
            'first' => 5,
            'page'  => 1,
        ],
        string $root = null,
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
            'operationName' => $operationName,
            'variables'     => $variables,
            'query'         => $query,
            'root'          => $root,
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
                    PDF::assertSee('Id');
                    PDF::assertSee('Product Name');
                    PDF::assertSee('Product Sku');
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
            new OrganizationUserDataProvider([
                'assets-view',
            ]),
            new ArrayDataProvider([
                'success-csv'         => [
                    new Ok(),
                    $assetFactory,
                    'csv',
                    'assets',
                    $query,
                ],
                'success-excel'       => [
                    new Ok(),
                    $assetFactory,
                    'excel',
                    'assets',
                    $query,
                ],
                'success-pdf'         => [
                    new Ok(),
                    $assetFactory,
                    'pdf',
                    'assets',
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
                    'assets',
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
                    'assets',
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
                    'assets',
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
                    'assets',
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
                    'assets',
                    $query,
                    [
                        'first' => 5,
                        'page'  => 1,
                    ],
                ],
                'success-csv-sub'     => [
                    new Ok(),
                    static function (TestCase $test, Organization $organization): int {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        Asset::factory()
                            ->for($reseller)
                            ->hasContacts(3)
                            ->create([ 'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986']);

                        // Data + Header
                        return 4;
                    },
                    'csv',
                    'asset',
                    /** @lang GraphQL */ <<<'GRAPHQL'
                    query asset($id:ID!){
                        asset(id:$id){
                            contacts {
                                id
                                name
                            }
                        }
                    }
                    GRAPHQL,
                    [
                        'first' => 5,
                        'page'  => 1,
                        'id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                    ],
                    'asset.contacts',
                ],
                'success-csv-empty'   => [
                    new Response(
                        new InternalServerError(),
                    ),
                    static function (TestCase $test, Organization $organization): int {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        Asset::factory()
                            ->for($reseller)
                            ->create([ 'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986']);

                        // Data + Header
                        return 0;
                    },
                    'csv',
                    'asset',
                    /** @lang GraphQL */ <<<'GRAPHQL'
                    query asset($id:ID!){
                        asset(id:$id){
                            contacts {
                                id
                                name
                            }
                        }
                    }
                    GRAPHQL,
                    [
                        'first' => 5,
                        'page'  => 1,
                        'id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                    ],
                    'asset.customer',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
