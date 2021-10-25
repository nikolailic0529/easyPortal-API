<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\Services\Search\Builders\Builder;
use Closure;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Paginated\Paginated
 */
class PaginatedTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateArgDefinition
     * @covers ::getLimitField
     * @covers ::getOffsetField
     *
     * @dataProvider dataProviderManipulateArgDefinition
     *
     * @param array<string, mixed> $settings
     */
    public function testManipulateArgDefinition(string $expected, array $settings): void {
        $this->setSettings($settings);

        $this->assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected($expected, '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @covers ::handleBuilder
     *
     * @dataProvider dataProviderHandleBuilder
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param array<mixed>                                 $args
     */
    public function testHandleBuilder(
        array $expected,
        Closure $builder,
        array $args,
    ): void {
        $directive = $this->app->make(Paginated::class);
        $builder   = $builder($this);
        $builder   = $directive->handleBuilder($builder, $args);

        $this->assertDatabaseQueryEquals($expected, $builder);
    }

    /**
     * @covers ::handleScoutBuilder
     *
     * @dataProvider dataProviderHandleScoutBuilder
     *
     * @param array{limit: ?int, offset: int} $expected
     * @param array<mixed>                    $args
     */
    public function testHandleScoutBuilder(array $expected, array $args): void {
        $directive = $this->app->make(Paginated::class);
        $builder   = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);
        $builder   = $directive->handleScoutBuilder($builder, $args);

        $this->assertEquals($expected, [
            'limit'  => $builder->limit,
            'offset' => $builder->offset ?? null,
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderManipulateArgDefinition(): array {
        return [
            'default limit is set'     => [
                '~expected-with-limit.graphql',
                [
                    'ep.pagination.limit.default' => 25,
                    'ep.pagination.limit.max'     => 123,
                ],
            ],
            'default limit is not set' => [
                '~expected-without-limit.graphql',
                [
                    'ep.pagination.limit.default' => null,
                    'ep.pagination.limit.max'     => 321,
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleBuilder(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'limit + offset' => [
                    [
                        'query'    => 'select * from `tmp` limit 123 offset 45',
                        'bindings' => [],
                    ],
                    [
                        'limit'  => 123,
                        'offset' => 45,
                    ],
                ],
                'limit'          => [
                    [
                        'query'    => 'select * from `tmp` limit 123',
                        'bindings' => [],
                    ],
                    [
                        'limit' => 123,
                    ],
                ],
                'offset'         => [
                    [
                        'query'    => 'select * from `tmp`',
                        'bindings' => [],
                    ],
                    [
                        'offset' => 123,
                    ],
                ],
                'none'           => [
                    [
                        'query'    => 'select * from `tmp`',
                        'bindings' => [],
                    ],
                    [
                        // empty
                    ],
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleScoutBuilder(): array {
        return [
            'limit + offset' => [
                [
                    'limit'  => 123,
                    'offset' => 45,
                ],
                [
                    'limit'  => 123,
                    'offset' => 45,
                ],
            ],
            'limit'          => [
                [
                    'limit'  => 123,
                    'offset' => null,
                ],
                [
                    'limit' => 123,
                ],
            ],
            'offset'         => [
                [
                    'limit'  => null,
                    'offset' => null,
                ],
                [
                    'offset' => 123,
                ],
            ],
            'none'           => [
                [
                    'limit'  => null,
                    'offset' => null,
                ],
                [
                    // empty
                ],
            ],
        ];
    }
    // </editor-fold>
}
