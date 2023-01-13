<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\Definitions\PaginatedOffsetDirective;
use App\Services\Search\Builders\Builder as SearchBuilder;
use Closure;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Paginated\PaginatedOffset
 */
class PaginatedOffsetTest extends TestCase {
    use WithoutGlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderHandleBuilder
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     */
    public function testHandleBuilder(array $expected, Closure $builder, ?int $limit): void {
        $directive = $this->app->make(PaginatedOffsetDirective::class);
        $builder   = $builder($this);
        $builder   = $directive->handleBuilder($builder, $limit);

        self::assertDatabaseQueryEquals($expected, $builder);
    }

    /**
     * @dataProvider dataProviderHandleScoutBuilder
     *
     * @param array{limit: ?int, offset: int} $expected
     */
    public function testHandleScoutBuilder(array $expected, ?int $limit): void {
        $directive = $this->app->make(PaginatedOffsetDirective::class);
        $builder   = $this->app->make(SearchBuilder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);
        $builder   = $directive->handleScoutBuilder($builder, $limit);

        self::assertEquals($expected, [
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
                'passed' => [
                    [
                        'query'    => 'select * from `tmp` offset 123',
                        'bindings' => [],
                    ],
                    123,
                ],
                'none'   => [
                    [
                        'query'    => 'select * from `tmp`',
                        'bindings' => [],
                    ],
                    null,
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleScoutBuilder(): array {
        return [
            'passed' => [
                [
                    'offset' => 123,
                ],
                123,
            ],
            'none'   => [
                [
                    'offset' => null,
                ],
                null,
            ],
        ];
    }
    // </editor-fold>
}
