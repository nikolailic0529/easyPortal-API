<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Closure;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Directives\Directive;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\BuilderDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Tests\DataProviders\Builders\EloquentBuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\PropertyOperator
 *
 * @phpstan-import-type BuilderFactory from BuilderDataProvider
 */
class PropertyOperatorTest extends TestCase {
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
        $operator  = $this->app->make(PropertyOperator::class);
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
        $factory = static function (self $test): Argument {
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
                'desc',
                $schema,
            );

            return $argument;
        };

        return (new CompositeDataProvider(
            new EloquentBuilderDataProvider(),
            new ArrayDataProvider([
                'property' => [
                    [
                        'query'    => <<<'SQL'
                            select
                                `tmp`.`a` as `key`,
                                count(*) as `count`
                            from
                                `tmp`
                            group by
                                `key`
                            order by
                                `key` desc
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    new Property('a'),
                    $factory,
                ],
            ]),
        ))->getData();
    }
    //</editor-fold>
}
