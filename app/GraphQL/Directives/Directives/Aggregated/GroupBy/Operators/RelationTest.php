<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Closure;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Directives\Directive;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\DataProviders\Builders\EloquentBuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\Relation
 *
 * @phpstan-import-type BuilderFactory from BuilderDataProvider
 */
class RelationTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
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
        $operator  = $this->app->make(Relation::class);
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
                        parent: Nested
                        parent_id: ID!
                    }

                    input Nested {
                        b: String
                    }
                    GRAPHQL,
                );
                $argument = $test->getGraphQLArgument(
                    '[SortByClauseNested!]',
                    [
                        [
                            'b' => $direction,
                        ],
                    ],
                    $schema,
                );

                return $argument;
            };
        };

        return (new CompositeDataProvider(
            new EloquentBuilderDataProvider(),
            new ArrayDataProvider([
                '`property` asc'  => [
                    [
                        'query'    => <<<'SQL'
                            select
                                `tmp`.`parent_id` as `__key`,
                                count(*) as `count`
                            from
                                `tmp`
                            group by
                                `__key`
                            order by
                                (
                                    select
                                        `laravel_reserved_0`.`b`
                                    from
                                        `tmp` as `laravel_reserved_0`
                                    where
                                        `laravel_reserved_0`.`id` = `tmp`.`parent_id`
                                    order by
                                        `laravel_reserved_0`.`b` asc
                                    limit
                                        1
                                ) asc
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    new Property('parent'),
                    $factory('asc'),
                ],
                '`property` desc' => [
                    [
                        'query'    => <<<'SQL'
                            select
                                `tmp`.`parent_id` as `__key`,
                                count(*) as `count`
                            from
                                `tmp`
                            group by
                                `__key`
                            order by
                                (
                                    select
                                        `laravel_reserved_0`.`b`
                                    from
                                        `tmp` as `laravel_reserved_0`
                                    where
                                        `laravel_reserved_0`.`id` = `tmp`.`parent_id`
                                    order by
                                        `laravel_reserved_0`.`b` desc
                                    limit
                                        1
                                ) desc
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    new Property('parent'),
                    $factory('desc'),
                ],
            ]),
        ))->getData();
    }
    //</editor-fold>
}
