<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Closure;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Directives\Directive;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\DataProviders\BuilderDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Tests\DataProviders\Builders\EloquentBuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\AsDate
 *
 * @phpstan-import-type BuilderFactory from BuilderDataProvider
 */
class AsDateTest extends TestCase {
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
        $operator  = $this->app->make(AsDate::class);
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

        return (new CompositeDataProvider(
            new EloquentBuilderDataProvider(),
            new ArrayDataProvider([
                '`property` asc'                          => [
                    [
                        'query'    => <<<'SQL'
                            select
                                DATE_FORMAT(`tmp`.`a`, '%Y-%m-%d') as `__key`,
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
                    new Property('a'),
                    $factory('asc'),
                ],
                '`property` desc'                         => [
                    [
                        'query'    => <<<'SQL'
                            select
                                DATE_FORMAT(`tmp`.`a`, '%Y-%m-%d') as `__key`,
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
                    new Property('a'),
                    $factory('desc'),
                ],
                '`property` asc (`date` cast)'            => [
                    [
                        'query'    => <<<'SQL'
                            select
                                `tmp`.`date_column` as `__key`,
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
                    new Property('date_column'),
                    $factory('asc'),
                ],
                '`property` desc (`immutable_date` cast)' => [
                    [
                        'query'    => <<<'SQL'
                            select
                                `tmp`.`date_column_immutable` as `__key`,
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
                    new Property('date_column_immutable'),
                    $factory('desc'),
                ],
            ]),
        ))->getData();
    }
    //</editor-fold>
}
