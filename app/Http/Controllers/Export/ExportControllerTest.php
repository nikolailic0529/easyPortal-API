<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\Exceptions\GraphQLQueryInvalid;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Closure;
use GraphQL\Server\OperationParams;
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
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\ValidationErrorResponse;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use Psr\Http\Message\ResponseInterface;
use Tests\Constraints\ContentTypes\CsvContentType;
use Tests\Constraints\ContentTypes\XlsxContentType;
use Tests\DataProviders\Http\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\Http\Users\OrgUserDataProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;
use Throwable;

use function count;
use function explode;
use function ob_end_clean;
use function ob_get_level;
use function trim;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\ExportController
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
     * @covers ::getHeaders
     *
     * @dataProvider dataProviderGetHeaders
     *
     * @param Query $parameters
     */
    public function testGetHeaders(mixed $expected, array $parameters): void {
        $controller = new class() extends ExportController {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritdoc
             */
            public function getHeaders(array $parameters): ?array {
                return parent::getHeaders($parameters);
            }
        };
        $actual     = $controller->getHeaders($parameters);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::csv
     *
     * @dataProvider dataProviderExport
     *
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param Closure(static, ?Organization, ?User): ?int $factory
     * @param array<string, mixed>                        $data
     * @param SettingsFactory                             $settingsFactory
     */
    public function testCvs(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
        array $data = [],
        mixed $settingsFactory = null,
    ): void {
        // Prepare
        [$org, $user, $data, $count] = $this->prepare(
            $orgFactory,
            $userFactory,
            $factory,
            $data,
            $settingsFactory,
        );

        // Fake
        Event::fake(QueryExported::class);

        // Errors
        if ($expected instanceof BadRequest) {
            self::expectExceptionObject(new StreamedResponseException(
                new GraphQLQueryInvalid(new OperationParams(), []),
            ));
        }

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
            $response->assertThat(new CsvContentType());
            $response->assertThat(new Response(new ClosureConstraint(
                static function (mixed $response) use ($count): bool {
                    self::assertInstanceOf(ResponseInterface::class, $response);

                    $content = trim((string) $response->getBody(), "\n");
                    $lines   = count(explode("\n", $content));

                    self::assertEquals((int) $count + 1, $lines);

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
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param Closure(static, ?Organization, ?User): ?int $factory
     * @param array<string, mixed>                        $data
     * @param SettingsFactory                             $settingsFactory
     */
    public function testXlsx(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
        array $data = [],
        mixed $settingsFactory = null,
    ): void {
        // Prepare
        [$org, $user, $data, $count] = $this->prepare(
            $orgFactory,
            $userFactory,
            $factory,
            $data,
            $settingsFactory,
        );

        // Fake
        Event::fake(QueryExported::class);

        // Errors
        if ($expected instanceof BadRequest) {
            self::expectExceptionObject(new StreamedResponseException(
                new GraphQLQueryInvalid(new OperationParams(), []),
            ));
        }

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
            $response->assertThat(new XlsxContentType());
            $response->assertThat(new Response(new ClosureConstraint(
                function (mixed $response) use ($count): bool {
                    self::assertInstanceOf(ResponseInterface::class, $response);

                    $sheets = [];
                    $file   = $this->getTempFile((string) $response->getBody());
                    $xlsx   = new XLSXReader();

                    $xlsx->open($file->getPathname());

                    foreach ($xlsx->getSheetIterator() as $sheet) {
                        foreach ($sheet->getRowIterator() as $row) {
                            $sheets[$sheet->getIndex()] = ($sheets[$sheet->getIndex()] ?? 0) + 1;
                        }
                    }

                    self::assertEquals([0 => (int) $count + 1], $sheets);

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
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param Closure(static, ?Organization, ?User): ?int $factory
     * @param array<string, mixed>                        $data
     * @param SettingsFactory                             $settingsFactory
     */
    public function testPdf(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
        array $data = [],
        mixed $settingsFactory = null,
    ): void {
        // Prepare
        [, , $data] = $this->prepare($orgFactory, $userFactory, $factory, $data, $settingsFactory);

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
     * @param OrganizationFactory                         $orgFactory
     * @param UserFactory                                 $userFactory
     * @param Closure(static, ?Organization, ?User): ?int $factory
     * @param array<string, mixed>                        $data
     * @param SettingsFactory                             $settingsFactory
     *
     * @return array{?Organization,?User,array<string,mixed>,?int}
     */
    protected function prepare(
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
        array $data = [],
        mixed $settingsFactory = null,
    ): array {
        $org   = $this->setOrganization($orgFactory);
        $user  = $this->setUser($userFactory, $org);
        $count = null;

        $this->setSettings($settingsFactory);

        if ($factory) {
            $count = $factory($this, $org, $user);
        }

        if (!$data) {
            $data = [
                'root'    => 'data.customers',
                'query'   => 'query { customers { id } }',
                'headers' => [
                    'id' => 'Id',
                ],
            ];
        }

        return [$org, $user, $data, $count];
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{mixed,mixed}>
     */
    public function dataProviderGetHeaders(): array {
        return [
            'normal' => [
                [
                    'A',
                    '',
                    'C',
                ],
                [
                    'root'    => '',
                    'query'   => '',
                    'headers' => [
                        'a' => 'A',
                        'b' => '',
                        'c' => 'C',
                    ],
                ],
            ],
            'empty'  => [
                null,
                [
                    'root'    => '',
                    'query'   => '',
                    'headers' => [
                        'a' => '',
                        'b' => '',
                        'c' => '',
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
            new AuthOrgDataProvider(),
            new OrgUserDataProvider([
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
                'no headers'              => [
                    new UnprocessableEntity(),
                    null,
                    [
                        'root'  => 'data.customers',
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
                    new ValidationErrorResponse([
                        'query' => ['Cannot query field "sfsdfsdsf" on type "Query".'],
                    ]),
                    null,
                    [
                        'root'  => 'data.customers',
                        'query' => 'query { sfsdfsdsf }',
                    ],
                ],
                'without pagination'      => [
                    new Ok(),
                    static function (self $test, Organization $org): int {
                        $assets = Asset::factory()->ownedBy($org)->count(5)->create();

                        return count($assets);
                    },
                    [
                        'root'    => 'data.assets',
                        'headers' => [
                            'id' => 'Id',
                        ],
                        'query'   => 'query { assets { id } }',
                    ],
                ],
                'with pagination'         => [
                    new Ok(),
                    static function (self $test, Organization $org): int {
                        $assets = Asset::factory()->ownedBy($org)->count(5)->create();

                        return count($assets);
                    },
                    [
                        'root'      => 'data.assets',
                        'query'     => <<<'GRAPHQL'
                            query getAssets($limit: Int, $offset: Int) {
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
                    static function (self $test, Organization $org): int {
                        Asset::factory()->ownedBy($org)->count(5)->create();

                        return 2;
                    },
                    [
                        'root'      => 'data.assets',
                        'headers'   => [
                            'id' => 'Id',
                        ],
                        'query'     => <<<'GRAPHQL'
                            query getAssets($limit: Int, $offset: Int) {
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
                    static function (self $test, Organization $org): int {
                        $assets = Asset::factory()->ownedBy($org)->count(5)->create();

                        return count($assets);
                    },
                    [
                        'root'      => 'data.assets',
                        'headers'   => [
                            'id' => 'Id',
                        ],
                        'query'     => <<<'GRAPHQL'
                            query getAssets($limit: Int, $offset: Int) {
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
