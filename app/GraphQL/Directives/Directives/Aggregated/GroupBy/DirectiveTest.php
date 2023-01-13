<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy;

use App\GraphQL\Directives\Definitions\AggregatedGroupByDirective;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Exceptions\FailedToCreateGroupClause;
use Closure;
use Exception;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\Client\ConditionTooManyProperties;
use LastDragon_ru\LaraASP\GraphQL\Testing\GraphQLExpectedSchema;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Tests\DataProviders\Builders\EloquentBuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function is_array;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Aggregated\GroupBy\Directive
 * @covers \App\GraphQL\Directives\Directives\Aggregated\GroupBy\Manipulator
 */
class DirectiveTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderManipulateArgDefinition
     *
     * @param Closure(static): GraphQLExpectedSchema $expected
     * @param Closure(static): void                  $prepare
     */
    public function testManipulateArgDefinition(Closure $expected, string $graphql, ?Closure $prepare = null): void {
        if ($prepare) {
            $prepare($this);
        }

        self::assertGraphQLSchemaEquals(
            $expected($this),
            $this->getTestData()->file($graphql),
        );
    }

    public function testManipulateArgDefinitionTypeRegistry(): void {
        $a = new InputObjectType([
            'name'   => 'A',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => Type::string(),
                ],
                [
                    'name' => 'flag',
                    'type' => Type::nonNull(Type::boolean()),
                ],
                [
                    'name' => 'value',
                    'type' => Type::int(),
                ],
            ],
        ]);
        $b = new InputObjectType([
            'name'   => 'B',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => Type::string(),
                ],
                [
                    'name' => 'child',
                    'type' => $a,
                ],
            ],
        ]);
        $c = new ObjectType([
            'name'   => 'C',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => Type::string(),
                ],
                [
                    'name' => 'flag',
                    'type' => Type::nonNull(Type::boolean()),
                ],
                [
                    'name' => 'list',
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::boolean()))),
                ],
            ],
        ]);
        $d = new ObjectType([
            'name'   => 'D',
            'fields' => [
                [
                    'name' => 'child',
                    'type' => Type::nonNull($c),
                ],
            ],
        ]);

        $registry = $this->app->make(TypeRegistry::class);
        $registry->register($a);
        $registry->register($b);
        $registry->register($c);
        $registry->register($d);

        self::assertGraphQLSchemaEquals(
            $this->getTestData()->file('~registry-expected.graphql'),
            $this->getTestData()->file('~registry.graphql'),
        );
    }

    public function testManipulateArgDefinitionTypeRegistryEmpty(): void {
        $type = new ObjectType([
            'name'   => 'TestType',
            'fields' => [
                [
                    'name' => 'list',
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::boolean()))),
                ],
            ],
        ]);

        self::expectExceptionObject(new FailedToCreateGroupClause('type TestType'));

        $registry = $this->app->make(TypeRegistry::class);
        $registry->register($type);

        $this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
              test(order: TestType @aggregatedGroupBy): TestType! @all
            }
            GRAPHQL,
        );
    }

    /**
     * @dataProvider dataProviderHandleBuilder
     *
     * @param array{query: string, bindings: array<mixed>}|Exception $expected
     * @param Closure(static): object                                $builderFactory
     */
    public function testHandleBuilder(
        array|Exception $expected,
        Closure $builderFactory,
        mixed $value,
    ): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $this->useGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                test(input: Test @aggregatedGroupBy): String! @mock
            }

            input Test {
                a: ID!
                b: String
                parent: Nested
                parent_id: ID!
            }

            input Nested {
                b: String
            }
            GRAPHQL,
        );

        $definitionNode = Parser::inputValueDefinition('input: AggregatedGroupByClauseTest!');
        $directiveNode  = Parser::directive('@test');
        $directive      = $this->app->make(AggregatedGroupByDirective::class)->hydrate($directiveNode, $definitionNode);
        $builder        = $builderFactory($this);
        $actual         = $directive->handleBuilder($builder, $value);

        if (is_array($expected)) {
            self::assertDatabaseQueryEquals($expected, $actual);
        } else {
            self::fail('Something wrong...');
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<string,array{Closure(self): GraphQLExpectedSchema, string}>
     */
    public function dataProviderManipulateArgDefinition(): array {
        return [
            'full' => [
                static function (self $test): GraphQLExpectedSchema {
                    return (new GraphQLExpectedSchema(
                        $test->getTestData()->file('~full-expected.graphql'),
                    ));
                },
                '~full.graphql',
                null,
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderHandleBuilder(): array {
        return (new CompositeDataProvider(
            new EloquentBuilderDataProvider(),
            new ArrayDataProvider([
                'empty'                     => [
                    [
                        'query'    => <<<'SQL'
                            select
                                *
                            from
                                `tmp`
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    [
                        // empty
                    ],
                ],
                'empty operators'           => [
                    [
                        'query'    => <<<'SQL'
                            select
                                *
                            from
                                `tmp`
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    [
                        // empty
                    ],
                ],
                'too many properties'       => [
                    new ConditionTooManyProperties(['a', 'b']),
                    [
                        'a' => 'asc',
                        'b' => 'desc',
                    ],
                ],
                'null'                      => [
                    [
                        'query'    => <<<'SQL'
                            select
                                *
                            from
                                `tmp`
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    null,
                ],
                'valid condition'           => [
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
                    [
                        'a' => 'asc',
                    ],
                ],
                'sort by relation property' => [
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
                    [
                        'parent' => [
                            [
                                'b' => 'asc',
                            ],
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
