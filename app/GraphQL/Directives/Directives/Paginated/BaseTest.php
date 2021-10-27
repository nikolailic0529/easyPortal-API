<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\Definitions\PaginatedDirective;
use App\Services\Search\Builders\Builder as SearchBuilder;
use Closure;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Paginated\Base
 */
class BaseTest extends TestCase {
    use WithoutOrganizationScope;
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
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
        $directive = $this->app->make(PaginatedDirective::class);
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
        $directive = $this->app->make(PaginatedDirective::class);
        $builder   = $this->app->make(SearchBuilder::class, [
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
