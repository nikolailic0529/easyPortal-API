<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Utils\Eloquent\Callbacks\OrderByKey
 */
class OrderByKeyTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param Closure(static): Builder<Model>              $builderFactory
     *
     */
    public function testInvoke(array $expected, Closure $builderFactory): void {
        $callback = new OrderByKey();
        $builder  = $builderFactory($this);
        $builder  = $callback($builder);

        self::assertDatabaseQueryEquals($expected, $builder);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, mixed>
     */
    public function dataProviderInvoke(): array {
        return [
            'unordered'                => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `test`
                        order by
                            `test`.`id` asc
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): Builder {
                    return OrderByKeyTest__Model::query();
                },
            ],
            'order by <name>'          => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `test`
                        order by
                            `name` asc,
                            `test`.`id` asc
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): Builder {
                    return OrderByKeyTest__Model::query()
                        ->orderBy('name');
                },
            ],
            'order by <key>'           => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `test`
                        order by
                            `id` desc,
                            `name` asc
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): Builder {
                    return OrderByKeyTest__Model::query()
                        ->orderBy('id', 'desc')
                        ->orderBy('name');
                },
            ],
            'order by <qualified key>' => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `test`
                        order by
                            `test`.`id` desc,
                            `name` asc
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): Builder {
                    return OrderByKeyTest__Model::query()
                        ->orderBy((new OrderByKeyTest__Model())->getQualifiedKeyName(), 'desc')
                        ->orderBy('name');
                },
            ],
            'union: unordered'         => [
                [
                    'query'    => <<<'SQL'
                        (
                            select
                                *
                            from
                                `test`
                        )
                        union
                            (
                                select
                                    *
                                from
                                    `test`
                            )
                        order by
                            `id` asc
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): Builder {
                    return OrderByKeyTest__Model::query()
                        ->union(OrderByKeyTest__Model::query()->toBase());
                },
            ],
            'union: order by <name>'   => [
                [
                    'query'    => <<<'SQL'
                        (
                            select
                                *
                            from
                                `test`
                        )
                        union
                            (
                                select
                                    *
                                from
                                    `test`
                            )
                        order by
                            `name` asc,
                            `id` asc
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): Builder {
                    return OrderByKeyTest__Model::query()
                        ->union(OrderByKeyTest__Model::query()->toBase())
                        ->orderBy('name');
                },
            ],
            'union: order by <key>'    => [
                [
                    'query'    => <<<'SQL'
                        (
                            select
                                *
                            from
                                `test`
                        )
                        union
                            (
                                select
                                    *
                                from
                                    `test`
                            )
                        order by
                            `id` desc,
                            `name` asc
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): Builder {
                    return OrderByKeyTest__Model::query()
                        ->union(OrderByKeyTest__Model::query()->toBase())
                        ->orderBy('id', 'desc')
                        ->orderBy('name');
                },
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class OrderByKeyTest__Model extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'test';
}
