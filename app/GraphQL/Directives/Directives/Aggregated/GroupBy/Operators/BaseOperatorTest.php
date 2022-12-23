<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Directives\Directive;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\BuilderDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\BaseOperator
 *
 * @phpstan-import-type BuilderFactory from BuilderDataProvider
 */
class BaseOperatorTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::call
     *
     * @dataProvider dataProviderCall
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param BuilderFactory                               $builderFactory
     * @param Closure(static): Argument                    $argumentFactory
     */
    public function testCall(
        array $expected,
        Closure $builderFactory,
        Property $property,
        Closure $argumentFactory,
    ): void {
        $operator  = new class() extends BaseOperator {
            protected function getKeyExpression(Builder $builder, string $column): string {
                return $builder->getGrammar()->wrap($builder->qualifyColumn($column));
            }

            public static function getName(): string {
                return 'test';
            }
        };
        $argument  = $argumentFactory($this);
        $directive = $this->app->make(Directive::class);
        $builder   = $builderFactory($this);
        $builder   = $operator->call($directive, $builder, $property, $argument);

        self::assertDatabaseQueryEquals($expected, $builder);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCall(): array {
        $builder = static function (): Builder {
            return (new class() extends Model {
                /**
                 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                 *
                 * @var string
                 */
                public $table = 'tmp';
            })->query();
        };
        $factory = static function (string $direction): Closure {
            return static function (self $test) use ($direction): Argument {
                $schema   = (string) $test->printGraphQLSchema(
                /** @lang GraphQL */
                    <<<'GRAPHQL'
                type Query {
                    test(input: Test @aggregatedGroupBy): String! @mock
                }

                input Test {
                    a: ID!
                    b: String
                }
                GRAPHQL,
                );
                $argument = $test->getGraphQLArgument(
                    'AggregatedGroupByTypeDirection!',
                    $direction,
                    $schema,
                );

                return $argument;
            };
        };

        return (new ArrayDataProvider([
            '`property` asc'  => [
                [
                    'query'    => <<<'SQL'
                            select
                                `tmp`.`a` as `__key`,
                                count(*) as `count`
                            from
                                `tmp`
                            group by
                                `__key`
                            order by
                                `__key` asc
                        SQL
                    ,
                    'bindings' => [],
                ],
                $builder,
                new Property('a'),
                $factory('asc'),
            ],
            '`property` desc' => [
                [
                    'query'    => <<<'SQL'
                            select
                                `tmp`.`a` as `__key`,
                                count(*) as `count`
                            from
                                `tmp`
                            group by
                                `__key`
                            order by
                                `__key` desc
                        SQL
                    ,
                    'bindings' => [],
                ],
                $builder,
                new Property('a'),
                $factory('desc'),
            ],
            'distinct'        => [
                [
                    'query'    => <<<'SQL'
                        select
                            `__key`,
                            count(*) as `count`
                        from
                            (
                                select
                                    distinct `tmp`.`a` as `__key`
                                from
                                    `tmp`
                            ) as `query`
                        group by
                            `__key`
                        order by
                            `__key` desc
                        SQL
                    ,
                    'bindings' => [],
                ],
                static function () use ($builder): Builder {
                    return $builder()->distinct();
                },
                new Property('a'),
                $factory('desc'),
            ],
        ]))->getData();
    }
    //</editor-fold>
}
