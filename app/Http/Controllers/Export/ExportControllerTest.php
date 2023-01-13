<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\Events\QueryExported;
use App\Models\Asset;
use App\Models\Data\City;
use App\Models\Data\Country;
use App\Models\Data\Coverage;
use App\Models\Data\Location;
use App\Models\Data\Product;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use Illuminate\Support\Facades\Event;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\UnprocessableEntity;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\DataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\ValidationErrorResponse;
use Tests\Constraints\Attachments\CsvAttachment;
use Tests\Constraints\Attachments\PdfAttachment;
use Tests\Constraints\Attachments\XlsxAttachment;
use Tests\DataProviders\Http\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\Http\Users\OrgUserDataProvider;
use Tests\Providers\Organizations\RootOrganizationProvider;
use Tests\Providers\Users\RootUserProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;
use Throwable;

use function ob_end_clean;
use function ob_get_level;
use function tap;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\ExportController
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 * @phpstan-import-type Query from ExportRequest
 */
class ExportControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderExportCsv
     *
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param SettingsFactory                             $settingsFactory
     * @param Closure(static, ?Organization, ?User): void $prepare
     * @param array<string, mixed>|null                   $data
     */
    public function testCvs(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
        array $data = null,
    ): void {
        // Prepare
        $org    = $this->setOrganization($orgFactory);
        $user   = $this->setUser($userFactory, $org);
        $data ??= [
            'root'    => 'data.assets',
            'query'   => 'query { assets { id } }',
            'columns' => [
                [
                    'name'  => 'Id',
                    'value' => 'id',
                ],
            ],
        ];

        $this->setSettings($settingsFactory);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        // Fake
        Event::fake(QueryExported::class);

        // Errors
        if ($expected instanceof Forbidden && $user?->organization_id === $org?->getKey()) {
            self::expectExceptionObject(new StreamedResponseException(
                new AuthorizationException('Unauthorized.'),
            ));
        }

        // Execute
        try {
            $level    = ob_get_level();
            $response = $this->postJson('/download/csv', $data)->assertThat($expected);
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }

        if ($response->isSuccessful()) {
            Event::assertDispatched(QueryExported::class);
        } else {
            Event::assertNothingDispatched();
        }
    }

    /**
     * @dataProvider dataProviderExportXlsx
     *
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param SettingsFactory                             $settingsFactory
     * @param Closure(static, ?Organization, ?User): void $prepare
     * @param array<string, mixed>|null                   $data
     */
    public function testXlsx(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
        array $data = null,
    ): void {
        // Prepare
        $org    = $this->setOrganization($orgFactory);
        $user   = $this->setUser($userFactory, $org);
        $data ??= [
            'root'    => 'data.assets',
            'query'   => 'query { assets { id } }',
            'columns' => [
                [
                    'name'  => 'Id',
                    'value' => 'id',
                ],
            ],
        ];

        $this->setSettings($settingsFactory);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        // Fake
        Event::fake(QueryExported::class);

        // Errors
        if ($expected instanceof Forbidden && $user?->organization_id === $org?->getKey()) {
            self::expectExceptionObject(new StreamedResponseException(
                new AuthorizationException('Unauthorized.'),
            ));
        }

        // Execute
        try {
            $level    = ob_get_level();
            $response = $this->postJson('/download/xlsx', $data)->assertThat($expected);
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }

        if ($response->isSuccessful()) {
            Event::assertDispatched(QueryExported::class);
        } else {
            Event::assertNothingDispatched();
        }
    }

    /**
     * @dataProvider dataProviderExportPdf
     *
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param SettingsFactory                             $settingsFactory
     * @param Closure(static, ?Organization, ?User): void $prepare
     * @param array<string, mixed>|null                   $data
     */
    public function testPdf(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
        array $data = null,
    ): void {
        // Prepare
        $org    = $this->setOrganization($orgFactory);
        $user   = $this->setUser($userFactory, $org);
        $data ??= [
            'root'    => 'data.assets',
            'query'   => 'query { assets { id } }',
            'columns' => [
                [
                    'name'  => 'Id',
                    'value' => 'id',
                ],
            ],
        ];

        $this->setSettings($settingsFactory);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        // Fake
        Event::fake(QueryExported::class);
        PDF::fake();

        // Errors
        if ($expected instanceof Forbidden && $user?->organization_id === $org?->getKey()) {
            self::expectExceptionObject(new StreamedResponseException(
                new AuthorizationException('Unauthorized.'),
            ));
        }

        // Execute
        try {
            $level    = ob_get_level();
            $response = $this->postJson('/download/pdf', $data)->assertThat($expected);
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }

        if ($response->isSuccessful()) {
            Event::assertDispatched(QueryExported::class);

            PDF::assertViewIs('exports.pdf');
            PDF::assertSee("<td style='font-weight:bold;'> Id</td>");
        } else {
            Event::assertNothingDispatched();
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderExportCsv(): array {
        return (new CompositeDataProvider(
            $this->getExportDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new CsvAttachment('export.csv', $this->getTestData()->file('.csv')),
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderExportXlsx(): array {
        return (new MergeDataProvider([
            'base'     => new CompositeDataProvider(
                $this->getExportDataProvider(),
                new ArrayDataProvider([
                    'ok' => [
                        new XlsxAttachment('export.xlsx', $this->getTestData()->file('.xlsx.csv')),
                    ],
                ]),
            ),
            'grouping' => new ArrayDataProvider([
                'ok' => [
                    new XlsxAttachment('export.xlsx', $this->getTestData()->file('.xlsx.groups.csv')),
                    new RootOrganizationProvider(),
                    new RootUserProvider(),
                    null,
                    static function (self $test, Organization $org): void {
                        $nicknameA = 'Nickname A';
                        $nicknameB = 'Nickname B';
                        $nicknameC = 'Nickname C';
                        $productA  = Product::factory()->create(['name' => 'Product A']);
                        $productB  = Product::factory()->create(['name' => 'Product B']);
                        $productC  = Product::factory()->create(['name' => 'Product C']);

                        Asset::factory()->ownedBy($org)->create([
                            'id'         => 'ebc69758-636d-4114-a199-df13b9924b52',
                            'nickname'   => $nicknameA,
                            'product_id' => $productA,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => 'bc08f999-fb97-4e40-b79e-bbef0a867472',
                            'nickname'   => $nicknameA,
                            'product_id' => $productB,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => '381fa79e-4ba9-4e74-b6cf-4c342fe41b0b',
                            'nickname'   => $nicknameA,
                            'product_id' => $productB,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => '0ceebf8c-0e85-4fe7-8d0a-adce7036b224',
                            'nickname'   => $nicknameB,
                            'product_id' => $productA,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => 'b1126ad6-2184-44f2-8042-a5b207de262e',
                            'nickname'   => $nicknameB,
                            'product_id' => $productA,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => '668259f3-8d22-4b41-9a7d-fba071805819',
                            'nickname'   => $nicknameB,
                            'product_id' => $productB,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => '223dc79e-a053-44f2-843d-630ad6527ff3',
                            'nickname'   => $nicknameB,
                            'product_id' => $productB,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => 'd4236d3c-d593-4c0f-a85a-0bcd5aead1ca',
                            'nickname'   => $nicknameB,
                            'product_id' => $productC,
                        ]);
                        Asset::factory()->ownedBy($org)->create([
                            'id'         => '1169195e-bd5f-4955-aa1a-a6288333f0ba',
                            'nickname'   => $nicknameC,
                            'product_id' => $productC,
                        ]);
                    },
                    [
                        'root'      => 'data.assets',
                        'query'     => /** @lang GraphQL */ <<<'GRAPHQL'
                            query assets($limit: Int, $offset: Int, $order: [SortByClauseAssetsSort!]) {
                                assets(limit: $limit, offset: $offset, order: $order) {
                                    id
                                    nickname
                                    product {
                                        name
                                    }
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => null,
                            'offset' => null,
                            'order'  => [
                                [
                                    'nickname' => 'asc',
                                ],
                                [
                                    'product' => [
                                        'name' => 'asc',
                                    ],
                                ],
                            ],
                        ],
                        'columns'   => [
                            [
                                'name'  => 'Nickname',
                                'group' => 'nickname',
                                'value' => 'nickname',
                            ],
                            [
                                'name'  => 'Product',
                                'group' => 'product.name',
                                'value' => 'product.name',
                            ],
                            [
                                'name'  => 'Id',
                                'value' => 'id',
                            ],
                        ],
                    ],
                ],
            ]),
        ]))->getData();
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderExportPdf(): array {
        return (new CompositeDataProvider(
            $this->getExportDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new PdfAttachment('export.pdf'),
                ],
            ]),
        ))->getData();
    }

    protected function getExportDataProvider(): DataProvider {
        $columns    = [
            [
                'name'  => 'Id',
                'value' => 'id',
            ],
            [
                'name'  => 'Name',
                'value' => 'or(nickname, product.name)',
            ],
            [
                'name'  => 'Location',
                'value' => 'concat(location.country.code, location.city.name)',
            ],
            [
                'name'  => 'Coverages Names',
                'value' => 'coverages.*.name',
            ],
            [
                'name'  => 'Coverages JSON',
                'value' => 'coverages',
            ],
        ];
        $properties = <<<'QUERY'
            id
            nickname
            product {
                name
            }
            location {
                country {
                    code
                }
                city {
                    name
                }
            }
            coverages {
                name
            }
            QUERY;
        $factory    = static function (self $test, Organization $org): void {
            $country   = Country::factory()->create([
                'code' => 'CA',
            ]);
            $city      = City::factory()->create([
                'name'       => 'City A',
                'country_id' => $country,
            ]);
            $location  = Location::factory()->create([
                'country_id' => $country,
                'city_id'    => $city,
            ]);
            $productA  = Product::factory()->create([
                'name' => 'Product A',
            ]);
            $productB  = Product::factory()->create([
                'name' => 'Product B',
            ]);
            $coverageA = Coverage::factory()->create([
                'id'   => '7a1ec28e-6665-4104-a22d-c7f6ff6ed560',
                'name' => 'Coverage A',
            ]);
            $coverageB = Coverage::factory()->create([
                'id'   => 'e1253c70-52fa-4283-abcf-0383074fe45b',
                'name' => 'Coverage B',
            ]);

            Asset::factory()
                ->ownedBy($org)
                ->hasAttached(Collection::make([$coverageA, $coverageB]))
                ->create([
                    'id'          => '10a76fcd-3bc2-4dfd-b4bb-5095eabe4ea4',
                    'nickname'    => 'Asset A',
                    'product_id'  => $productA,
                    'location_id' => $location,
                ]);
            Asset::factory()
                ->ownedBy($org)
                ->hasAttached($coverageB)
                ->create([
                    'id'          => '41baddd6-a048-46a8-952f-0c7d4c1b9a33',
                    'nickname'    => null,
                    'product_id'  => $productA,
                    'location_id' => null,
                ]);
            Asset::factory()
                ->ownedBy($org)
                ->create([
                    'id'          => '5d153624-a162-4c61-bfef-76fe0acd7fe0',
                    'nickname'    => null,
                    'product_id'  => $productB,
                    'location_id' => null,
                ]);
        };

        return new CompositeDataProvider(
            new AuthOrgDataProvider(),
            new OrgUserDataProvider([
                'customers-view',
            ]),
            new ArrayDataProvider([
                'no query'                => [
                    new ExpectedFinal(new UnprocessableEntity()),
                    null,
                    null,
                    [
                        'root' => 'data.customers',
                    ],
                ],
                'no root'                 => [
                    new ExpectedFinal(new UnprocessableEntity()),
                    null,
                    null,
                    [
                        'query' => 'query { customers { id } }',
                    ],
                ],
                'no columns'              => [
                    new ExpectedFinal(new UnprocessableEntity()),
                    null,
                    null,
                    [
                        'root'  => 'data.customers',
                        'query' => 'query { customers { id } }',
                    ],
                ],
                'empty columns'           => [
                    new ExpectedFinal(new UnprocessableEntity()),
                    null,
                    null,
                    [
                        'root'    => 'data.customers',
                        'query'   => 'query { customers { id } }',
                        'columns' => [
                            // empty
                        ],
                    ],
                ],
                'no column name'          => [
                    new ExpectedFinal(new UnprocessableEntity()),
                    null,
                    null,
                    [
                        'root'    => 'data.customers',
                        'query'   => 'query { customers { id } }',
                        'columns' => [
                            [
                                'name'  => '',
                                'value' => 'abc',
                            ],
                        ],
                    ],
                ],
                'mutation'                => [
                    new ExpectedFinal(new UnprocessableEntity()),
                    null,
                    null,
                    [
                        'root'  => 'data.customers',
                        'query' => 'mutation { assets { id } }',
                    ],
                ],
                'invalid query'           => [
                    new ExpectedFinal(
                        new ValidationErrorResponse([
                            'query' => ['Cannot query field "sfsdfsdsf" on type "Query".'],
                        ]),
                    ),
                    null,
                    null,
                    [
                        'root'  => 'data.customers',
                        'query' => 'query { sfsdfsdsf }',
                    ],
                ],
                'without pagination'      => [
                    new UnknownValue(),
                    null,
                    $factory,
                    [
                        'root'    => 'data.assets',
                        'query'   => "query { assets { {$properties} } }",
                        'columns' => $columns,
                    ],
                ],
                'with pagination'         => [
                    new UnknownValue(),
                    null,
                    $factory,
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<GRAPHQL
                            query getAssets(\$limit: Int, \$offset: Int) {
                                assets(limit: \$limit, offset: \$offset) {
                                    {$properties}
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => null,
                            'offset' => null,
                        ],
                        'columns'   => $columns,
                    ],
                ],
                'with limit'              => [
                    new UnknownValue(),
                    null,
                    $factory,
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<GRAPHQL
                            query getAssets(\$limit: Int, \$offset: Int) {
                                assets(limit: \$limit, offset: \$offset) {
                                    {$properties}
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => 3,
                            'offset' => null,
                        ],
                        'columns'   => $columns,
                    ],
                ],
                'with chunked pagination' => [
                    new UnknownValue(),
                    [
                        'ep.export.chunk' => 2,
                    ],
                    $factory,
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<GRAPHQL
                            query getAssets(\$limit: Int, \$offset: Int) {
                                assets(limit: \$limit, offset: \$offset) {
                                    {$properties}
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => 5,
                            'offset' => null,
                        ],
                        'columns'   => $columns,
                    ],
                ],
                // todo(REST): Right now there is no way to view merged cells :( so we test the query only.
                'with grouping'           => [
                    new UnknownValue(),
                    null,
                    $factory,
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<GRAPHQL
                            query getAssets(\$limit: Int, \$offset: Int) {
                                assets(limit: \$limit, offset: \$offset) {
                                    {$properties}
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => null,
                            'offset' => null,
                        ],
                        'columns'   => tap($columns, static function (array &$columns): void {
                            $columns[0]['group'] = 'id';
                        }),
                    ],
                ],
            ]),
        );
    }
    // </editor-fold>
}
