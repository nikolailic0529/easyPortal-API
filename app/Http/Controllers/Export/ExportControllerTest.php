<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\XLSX\Sheet;
use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use Illuminate\Support\Facades\Event;
use LastDragon_ru\LaraASP\Testing\Constraints\ClosureConstraint;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentTypes\PdfContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\BadRequest;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\UnprocessableEntity;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Psr\Http\Message\ResponseInterface;
use Tests\DataProviders\Http\Organizations\OrganizationDataProvider;
use Tests\DataProviders\Http\Users\OrganizationUserDataProvider;
use Tests\ResponseTypes\CsvContentType;
use Tests\ResponseTypes\XlsxContentType;
use Tests\TestCase;
use Throwable;

use function count;
use function explode;
use function json_encode;
use function ob_end_clean;
use function ob_get_level;
use function trim;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\ExportController
 */
class ExportControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getHeaders
     * @covers ::getHeader
     *
     * @dataProvider dataProviderGetHeaders
     */
    public function testGetHeaders(mixed $expected, mixed $value): void {
        $controller = new class() extends ExportController {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getHeaders(array $item, string $prefix = null): array {
                return parent::getHeaders($item, $prefix);
            }
        };
        $actual     = $controller->getHeaders($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::getHeaderValue
     *
     * @dataProvider dataProviderGetHeaderValue
     */
    public function testGetHeaderValue(mixed $expected, string $header, mixed $item): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $controller = new class() extends ExportController {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getHeaderValue(string $header, array $item): mixed {
                return parent::getHeaderValue($header, $item);
            }
        };
        $actual     = $controller->getHeaderValue($header, $item);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::getValue
     *
     * @dataProvider dataProviderGetValue
     */
    public function testGetValue(mixed $expected, mixed $value): void {
        $controller = new class() extends ExportController {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getValue(mixed $value): mixed {
                return parent::getValue($value);
            }
        };
        $actual     = $controller->getValue($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::getItemValue
     *
     * @dataProvider dataProviderGetItemValue
     *
     * @param array<mixed> $value
     */
    public function testGetItemValue(mixed $expected, string $path, array $value): void {
        $controller = new class() extends ExportController {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getItemValue(string $path, array $item): mixed {
                return parent::getItemValue($path, $item);
            }
        };
        $actual     = $controller->getItemValue($path, $value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::csv
     *
     * @dataProvider dataProviderExport
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $settings
     */
    public function testCvs(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $factory = null,
        array $data = [],
        array $settings = null,
    ): void {
        // Prepare
        [$organization, $user, $data, $count] = $this->prepare(
            $organizationFactory,
            $userFactory,
            $factory,
            $data,
            $settings,
        );

        // Fake
        Event::fake(QueryExported::class);

        // Errors
        if ($expected instanceof BadRequest) {
            self::expectExceptionObject(new StreamedResponseException(
                new GraphQLQueryInvalid([]),
            ));
        }

        if ($expected instanceof Forbidden && $user?->organization_id === $organization?->getKey()) {
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
            $response->assertThat(new CsvContentType());
            $response->assertThat(new Response(new ClosureConstraint(
                function (ResponseInterface $response) use ($count): bool {
                    $content = trim((string) $response->getBody(), "\n");
                    $lines   = count(explode("\n", $content));

                    self::assertEquals($count + 1, $lines);

                    return true;
                },
            )));

            Event::assertDispatched(QueryExported::class);
        } else {
            Event::assertNothingDispatched();
        }
    }

    /**
     * @covers ::xlsx
     *
     * @dataProvider dataProviderExport
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $settings
     */
    public function testXlsx(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $factory = null,
        array $data = [],
        array $settings = null,
    ): void {
        // Prepare
        [$organization, $user, $data, $count] = $this->prepare(
            $organizationFactory,
            $userFactory,
            $factory,
            $data,
            $settings,
        );

        // Fake
        Event::fake(QueryExported::class);

        // Errors
        if ($expected instanceof BadRequest) {
            self::expectExceptionObject(new StreamedResponseException(
                new GraphQLQueryInvalid([]),
            ));
        }

        if ($expected instanceof Forbidden && $user?->organization_id === $organization?->getKey()) {
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
            $response->assertThat(new XlsxContentType());
            $response->assertThat(new Response(new ClosureConstraint(
                function (ResponseInterface $response) use ($count): bool {
                    $sheets = [];
                    $file   = $this->getTempFile((string) $response->getBody());
                    $xlsx   = ReaderEntityFactory::createXLSXReader();

                    $xlsx->open($file->getPathname());

                    foreach ($xlsx->getSheetIterator() as $sheet) {
                        /** @var Sheet $sheet */
                        foreach ($sheet->getRowIterator() as $row) {
                            $sheets[$sheet->getIndex()] = ($sheets[$sheet->getIndex()] ?? 0) + 1;
                        }
                    }

                    self::assertEquals([0 => $count + 1], $sheets);

                    return true;
                },
            )));

            Event::assertDispatched(QueryExported::class);
        } else {
            Event::assertNothingDispatched();
        }
    }

    /**
     * @covers ::pdf
     *
     * @dataProvider dataProviderExport
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $settings
     */
    public function testPdf(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $factory = null,
        array $data = [],
        array $settings = null,
    ): void {
        // Prepare
        [, , $data] = $this->prepare($organizationFactory, $userFactory, $factory, $data, $settings);

        // Fake
        Event::fake(QueryExported::class);
        PDF::fake();

        // Execute
        $response = $this->postJson('/download/pdf', $data)->assertThat($expected);

        if ($response->isSuccessful()) {
            $response->assertThat(new PdfContentType());

            PDF::assertViewIs('exports.pdf');
            PDF::assertSee("<td style='font-weight:bold;'> Id</td>");

            Event::assertDispatched(QueryExported::class);
        } else {
            Event::assertNothingDispatched();
        }
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $settings
     *
     * @return array{?Organization,?User,array<string,mixed>,?int}
     */
    protected function prepare(
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $factory = null,
        array $data = [],
        array $settings = null,
    ): array {
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $count        = null;

        $this->setSettings($settings);

        if ($factory) {
            $count = $factory($this, $organization, $user);
        }

        if (!$data) {
            $data = [
                'root'  => 'data.customers',
                'query' => 'query { customers { id } }',
            ];
        }

        return [$organization, $user, $data, $count];
    }

    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{mixed,mixed}>
     */
    public function dataProviderGetHeaders(): array {
        return [
            'assoc'               => [
                [
                    'a'       => 'A',
                    'b'       => 'B',
                    'c.aa'    => 'C Aa',
                    'd.aa.aa' => 'D Aa Aa',
                    'e.aa.aa' => 'E Aa Aa',
                ],
                [
                    'a' => 123,
                    'b' => [1, 2, 3],
                    'c' => ['aa' => 'aa'],
                    'd' => ['aa' => ['aa' => 123]],
                    'e' => ['aa' => ['aa' => ['a']]],
                ],
            ],
            'assoc with one item' => [
                [
                    'test' => 'Test',
                ],
                [
                    'test' => [
                        [
                            'a' => 'Aaaa',
                            'b' => 'B',
                        ],
                        [
                            'a' => 'Aaaa',
                            'b' => 'B',
                        ],
                    ],
                ],
            ],
            'list'                => [
                [
                    // empty
                ],
                [
                    [
                        'a' => 'Aaaa',
                    ],
                    [
                        'a' => 'Aaaa',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{mixed,string,mixed}>
     */
    public function dataProviderGetHeaderValue(): array {
        return [
            'simple header'     => [
                123,
                'a',
                [
                    'a' => 123,
                ],
            ],
            'function: unknown' => [
                new HeadersUnknownFunction('unknown'),
                'unknown(a)',
                [
                    'b' => 123,
                ],
            ],
            'function: concat'  => [
                '123 ab',
                'concat(a,c , b.a, d)',
                [
                    'a' => 123,
                    'b' => ['a' => 'ab'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{mixed,mixed}>
     */
    public function dataProviderGetValue(): array {
        return [
            'int'                          => [123, 123],
            'bool'                         => [true, true],
            'float'                        => [12.3, 12.3],
            'array'                        => [
                json_encode([
                    ['a' => 123, 'b' => 'b'],
                ]),
                [
                    ['a' => 123, 'b' => 'b'],
                ],
            ],
            'array assoc'                  => [
                json_encode(['a' => 123, 'b' => 'b']),
                [
                    'a' => 123,
                    'b' => 'b',
                ],
            ],
            'array of scalars'             => [
                'a, b',
                [
                    'a',
                    'b',
                ],
            ],
            'array of array with one item' => [
                '123, b',
                [
                    ['a' => 123],
                    ['b' => 'b'],
                ],
            ],
            'array of array'               => [
                json_encode([
                    ['a' => 123],
                    ['b' => 'b', 'c' => 'c'],
                ]),
                [
                    ['a' => 123],
                    ['b' => 'b', 'c' => 'c'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{string,array<mixed>}>
     */
    public function dataProviderGetItemValue(): array {
        return [
            'a'                => [
                123,
                'a',
                [
                    'a' => 123,
                ],
            ],
            'a (not exists)'   => [
                null,
                'a',
                [
                    'b' => 123,
                ],
            ],
            'a.b (not exists)' => [
                null,
                'a.b',
                [
                    'a' => 123,
                ],
            ],
            'a.b'              => [
                123,
                'a.b',
                [
                    'a' => ['b' => 123],
                ],
            ],
            'a.b (array)'      => [
                '1, 2, 3',
                'a.b',
                [
                    'a' => [
                        ['b' => 1],
                        ['b' => 2],
                        ['b' => 3],
                    ],
                ],
            ],
            'a.b.c (array)'    => [
                '1, 2, 3',
                'a.b.c',
                [
                    'a' => [
                        ['b' => ['c' => 1]],
                        ['b' => ['c' => 2]],
                        ['b' => ['c' => 3]],
                    ],
                ],
            ],
            'getValue called'  => [
                '[{"c":1},{"c":2},{"c":3,"d":2}]',
                'a.b',
                [
                    'a' => [
                        ['b' => ['c' => 1]],
                        ['b' => ['c' => 2]],
                        ['b' => ['c' => 3, 'd' => 2]],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderExport(): array {
        return (new CompositeDataProvider(
            new OrganizationDataProvider(),
            new OrganizationUserDataProvider([
                'customers-view',
            ]),
            new ArrayDataProvider([
                'no query'                => [
                    new UnprocessableEntity(),
                    null,
                    [
                        'root' => 'data.customers',
                    ],
                ],
                'no root'                 => [
                    new UnprocessableEntity(),
                    null,
                    [
                        'query' => 'query { customers { id } }',
                    ],
                ],
                'mutation'                => [
                    new UnprocessableEntity(),
                    null,
                    [
                        'root'  => 'data.customers',
                        'query' => 'mutation { assets { id } }',
                    ],
                ],
                'invalid query'           => [
                    new BadRequest(),
                    null,
                    [
                        'root'  => 'data.customers',
                        'query' => 'query { sfsdfsdsf }',
                    ],
                ],
                'without pagination'      => [
                    new Ok(),
                    static function (self $test, Organization $organization): int {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        $assets   = Asset::factory()->count(5)->create([
                            'reseller_id' => $reseller,
                        ]);

                        return count($assets);
                    },
                    [
                        'root'  => 'data.assets',
                        'query' => 'query { assets { id } }',
                    ],
                ],
                'with pagination'         => [
                    new Ok(),
                    static function (self $test, Organization $organization): int {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        $assets   = Asset::factory()->count(5)->create([
                            'reseller_id' => $reseller,
                        ]);

                        return count($assets);
                    },
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<'GRAPHQL'
                            query getAssets($limit: Int!, $offset: Int!) {
                                assets(limit: $limit, offset: $offset) {
                                    id
                                    product {
                                        sku
                                    }
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => null,
                            'offset' => null,
                        ],
                        'headers'   => [
                            'id'          => 'Id',
                            'product.sku' => 'Product',
                        ],
                    ],
                ],
                'with limit'              => [
                    new Ok(),
                    static function (self $test, Organization $organization): int {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        Asset::factory()->count(5)->create([
                            'reseller_id' => $reseller,
                        ]);

                        return 2;
                    },
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<'GRAPHQL'
                            query getAssets($limit: Int!, $offset: Int!) {
                                assets(limit: $limit, offset: $offset) {
                                    id
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => 2,
                            'offset' => null,
                        ],
                    ],
                ],
                'with chunked pagination' => [
                    new Ok(),
                    static function (self $test, Organization $organization): int {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        $assets   = Asset::factory()->count(5)->create([
                            'reseller_id' => $reseller,
                        ]);

                        return count($assets);
                    },
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<'GRAPHQL'
                            query getAssets($limit: Int!, $offset: Int!) {
                                assets(limit: $limit, offset: $offset) {
                                    id
                                }
                            }
                            GRAPHQL
                        ,
                        'variables' => [
                            'limit'  => 5,
                            'offset' => null,
                        ],
                    ],
                    [
                        'ep.export.chunk' => 2,
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
