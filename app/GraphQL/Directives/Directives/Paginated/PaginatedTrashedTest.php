<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\Definitions\PaginatedTrashedDirective;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\DataProviders\Builders\QueryBuilderDataProvider;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Paginated\PaginatedTrashed
 */
class PaginatedTrashedTest extends TestCase {
    use WithoutGlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderHandleBuilder
     *
     * @param array{query: string, bindings: array<mixed>}                          $expected
     * @param Closure(static): EloquentBuilder<Model>|Closure(static): QueryBuilder $builder
     */
    public function testHandleBuilder(array $expected, Closure $builder, mixed $value): void {
        $directive = $this->app->make(PaginatedTrashedDirective::class);
        $builder   = $builder($this);
        $builder   = $directive->handleBuilder($builder, $value);

        self::assertDatabaseQueryEquals($expected, $builder);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleBuilder(): array {
        return (new MergeDataProvider([
            new CompositeDataProvider(
                new QueryBuilderDataProvider(),
                new ArrayDataProvider([
                    (string) Trashed::include() => [
                        [
                            'query'    => 'select * from `tmp`',
                            'bindings' => [],
                        ],
                        Trashed::include(),
                    ],
                    (string) Trashed::only()    => [
                        [
                            'query'    => 'select * from `tmp`',
                            'bindings' => [],
                        ],
                        Trashed::only(),
                    ],
                ]),
            ),
            new CompositeDataProvider(
                new ArrayDataProvider([
                    'Builder' => [
                        new UnknownValue(),
                        static function (): Builder {
                            return (new class() extends Model {
                                use SoftDeletes;

                                /**
                                 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                                 *
                                 * @var string
                                 */
                                public $table = 'tmp';
                            })->query();
                        },
                    ],
                ]),
                new ArrayDataProvider([
                    (string) Trashed::include() => [
                        [
                            'query'    => 'select * from `tmp`',
                            'bindings' => [],
                        ],
                        Trashed::include(),
                    ],
                    (string) Trashed::only()    => [
                        [
                            'query'    => 'select * from `tmp` where `tmp`.`deleted_at` is not null',
                            'bindings' => [],
                        ],
                        Trashed::only(),
                    ],
                    'null'                      => [
                        [
                            'query'    => 'select * from `tmp` where `tmp`.`deleted_at` is null',
                            'bindings' => [],
                        ],
                        null,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
