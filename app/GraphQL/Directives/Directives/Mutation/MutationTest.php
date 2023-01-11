<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutGlobalScopes;

use function json_encode;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Mutation\Mutation
 */
class MutationTest extends TestCase {
    use WithoutGlobalScopes;
    use WithGraphQLSchema;

    public function testManipulateFieldDefinition(): void {
        self::assertNotNull($this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                mocked: String @mock
            }
            type Mutation {
                model: ModelMutations!
                @mutation(
                    resolver: "resolver",
                )
            }

            type ModelMutations {
                mutate: String
                @mutationCall(
                    resolver: "resolver",
                )

                mutation: ModelMutations
                @mutation(
                    resolver: "resolver",
                )
            }
            GRAPHQL,
        ));
    }

    public function testManipulateFieldDefinitionNoArguments(): void {
        self::expectExceptionObject(new DefinitionException(
            'Directive `@mutation` required at least one of `model`, `builder`, `relation`, `resolver` argument.',
        ));

        $this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                mocked: String @mock
            }
            type Mutation {
                model: ModelMutations!
                @mutation
            }

            type ModelMutations {
                mocked: String @mock
            }
            GRAPHQL,
        );
    }

    public function testManipulateFieldDefinitionFieldIsNotType(): void {
        self::expectExceptionObject(new DefinitionException(
            'Field `Mutation.model` must be a Type.',
        ));

        $this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                mocked: String @mock
            }
            type Mutation {
                model: String!
                @mutation(
                    resolver: "resolver",
                )
            }
            GRAPHQL,
        );
    }

    public function testManipulateFieldDefinitionFieldIsNotMutation(): void {
        self::expectExceptionObject(new DefinitionException(
            'Field `ModelMutations.mutation` must use one of '.
            '`@mutation`, `@mutationCall`, `@mutationCreate`, `@mutationMutate`'.
            ' directives.',
        ));

        $this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                mocked: String @mock
            }
            type Mutation {
                model: ModelMutations!
                @mutation(
                    resolver: "resolver",
                )
            }

            type ModelMutations {
                mutate: String
                @mutationCall(
                    resolver: "resolver",
                )

                mutation: String
            }
            GRAPHQL,
        );
    }

    public function testResolveField(): void {
        $customer = Customer::factory()->create();
        $resolver = json_encode(MutationTest_Mutation::class);
        $builder  = json_encode(MutationTest_Builder::class);

        $this->override(MutationTest_Mutation::class, static function (MockInterface $mock) use ($customer): void {
            $mock
                ->shouldReceive('__invoke')
                ->withArgs(static function (Customer $c) use ($customer): bool {
                    return $customer->is($c);
                })
                ->once()
                ->andReturn($customer->getKey());
        });

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    mocked: String @mock
                }
                type Mutation {
                    model(id: ID! @eq): ModelMutations!
                    @mutation(
                        builder: {$builder},
                    )
                }

                type ModelMutations {
                    mutate: String
                    @mutationCall(
                        resolver: {$resolver},
                    )
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!) {
                    model(id: $id) {
                        mutate
                    }
                }
                GRAPHQL,
                [
                    'id' => $customer->getKey(),
                ],
            )
            ->assertThat(new OkResponse(AnySchema::class, [
                'data' => [
                    'model' => [
                        'mutate' => $customer->getKey(),
                    ],
                ],
            ]));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationTest_Builder {
    public function __invoke(): EloquentBuilder {
        return Customer::query();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationTest_Mutation {
    public function __invoke(): mixed {
        return null;
    }
}
