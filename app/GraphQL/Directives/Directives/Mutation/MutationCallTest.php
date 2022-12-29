<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\EmptyContext;
use App\GraphQL\Directives\Directives\Mutation\Rules\CustomRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;
use App\Models\Customer;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSetFactory;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutGlobalScopes;

use function array_map;
use function is_object;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Mutation\MutationCall
 */
class MutationCallTest extends TestCase {
    use WithoutGlobalScopes;
    use WithGraphQLSchema;

    /**
     * @covers ::resolveField
     */
    public function testResolveField(): void {
        $customer = Customer::factory()->create();
        $mutation = json_encode(MutationCallTest_Mutation::class);
        $builder  = json_encode(MutationCallTest_Builder::class);

        $this->override(MutationCallTest_Mutation::class, static function (MockInterface $mock) use ($customer): void {
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
                    call: String
                    @mutationCall(
                        resolver: {$mutation},
                    )
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!) {
                    model(id: $id) {
                        call
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
                        'call' => $customer->getKey(),
                    ],
                ],
            ]));
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldNested(): void {
        $customer = Customer::factory()->create();
        $resolver = json_encode(MutationCallTest_NullResolver::class);
        $mutation = json_encode(MutationCallTest_Mutation::class);
        $builder  = json_encode(MutationCallTest_Builder::class);

        $this->override(MutationCallTest_Mutation::class, static function (MockInterface $mock) use ($customer): void {
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
                    nested: ModelNestedMutations
                    @mutation(
                        resolver: {$resolver},
                    )
                }

                type ModelNestedMutations {
                    call: String
                    @mutationCall(
                        resolver: {$mutation},
                    )
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!) {
                    model(id: $id) {
                        nested {
                            call
                        }
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
                        'nested' => [
                            'call' => $customer->getKey(),
                        ],
                    ],
                ],
            ]));
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldValidate(): void {
        $customer   = Customer::factory()->create();
        $mutation   = json_encode(MutationCallTest_Mutation::class);
        $builder    = json_encode(MutationCallTest_Builder::class);
        $directives = $this->app->make(DirectiveLocator::class);

        $directives->setResolved('isValid', MutationCallTest_Directive::class);

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
                    call(input: Parameters): String
                    @mutationCall(
                        resolver: {$mutation},
                    )
                }

                input Parameters {
                    test: Int @isValid
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!) {
                    model(id: $id) {
                        call(input: { test: 123 })
                    }
                }
                GRAPHQL,
                [
                    'id' => $customer->getKey(),
                ],
            )
            ->assertThat(new GraphQLValidationError('model', [
                'input.test' => ['validation.rule.isValid'],
            ]));
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldValidateField(): void {
        $customer   = Customer::factory()->create();
        $mutation   = json_encode(MutationCallTest_Mutation::class);
        $builder    = json_encode(MutationCallTest_Builder::class);
        $directives = $this->app->make(DirectiveLocator::class);

        $directives->setResolved('isValid', MutationCallTest_Directive::class);

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
                    call(input: Parameters): String
                    @isValid
                    @mutationCall(
                        resolver: {$mutation},
                    )
                }

                input Parameters {
                    test: Int
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!) {
                    model(id: $id) {
                        call(input: { test: 123 })
                    }
                }
                GRAPHQL,
                [
                    'id' => $customer->getKey(),
                ],
            )
            ->assertThat(new GraphQLValidationError('model', [
                'context' => ['validation.rule.isValid'],
            ]));
    }

    /**
     * @covers ::getRules
     */
    public function testGetRules(): void {
        // Mocks
        $locator  = $this->app->make(DirectiveLocator::class);
        $factory  = $this->app->make(Factory::class);
        $mutation = new class($locator, $factory) extends MutationCall {
            /**
             * @inheritDoc
             */
            public function getRules(Context $context, ArgumentSet $set, string $prefix = null): array {
                return parent::getRules($context, $set, $prefix);
            }
        };

        // Schema
        $directives = $this->app->make(DirectiveLocator::class);
        $directives->setResolved('isValid', MutationCallTest_Directive::class);
        $directives->setResolved('isValidLaravel', MutationCallTest_LaravelDirective::class);

        $this->useGraphQLSchema(
            /* @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                mocked: String @mock
            }

            type Mutation {
                test(input: TestInput): Boolean @mock
            }

            input TestInput {
                a: Int @isValid
                b: [TestItem] @isValid
                c: Boolean
            }

            input TestItem {
                a: Int @isValid @isValid
                c: Boolean @isValidLaravel
            }
            GRAPHQL,
        );

        $input = $this->getGraphQLSchemaBuilder()->schema()->getType('TestInput')?->astNode;

        self::assertInstanceOf(InputObjectTypeDefinitionNode::class, $input);

        // Test (no args)
        $args     = [];
        $set      = $this->app->make(ArgumentSetFactory::class)->wrapArgs($input, $args);
        $context  = new EmptyContext(null);
        $actual   = $mutation->getRules($context, $set);
        $expected = [];

        self::assertEquals($expected, array_map(static function (array $rules): array {
            return array_map(static function (object|string $rule): string {
                return is_object($rule) ? $rule::class : $rule;
            }, $rules);
        }, $actual));

        // Test (with input)
        $args     = [
            'a' => 123,
            'b' => [
                null,
                [
                    'c' => true,
                ],
                [
                    'a' => 123,
                    'c' => true,
                ],
            ],
        ];
        $set      = $this->app->make(ArgumentSetFactory::class)->wrapArgs($input, $args);
        $context  = new EmptyContext(null);
        $actual   = $mutation->getRules($context, $set);
        $expected = [
            'a'     => [MutationCallTest_Rule::class],
            'b.*'   => [MutationCallTest_Rule::class],
            'b.1.c' => ['required_without:b.1.a'],
            'b.2.a' => [MutationCallTest_Rule::class, MutationCallTest_Rule::class],
            'b.2.c' => ['required_without:b.2.a'],
        ];

        self::assertEquals($expected, array_map(static function (array $rules): array {
            return array_map(static function (object|string $rule): string {
                return is_object($rule) ? $rule::class : $rule;
            }, $rules);
        }, $actual));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCallTest_Builder {
    public function __invoke(): EloquentBuilder {
        return Customer::query();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCallTest_Mutation {
    public function __invoke(): mixed {
        return null;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCallTest_NullResolver {
    public function __invoke(): mixed {
        return null;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCallTest_Directive extends CustomRule {
    public static function definition(): string {
        return 'directive @isValid';
    }

    protected function getRuleClass(): string {
        return MutationCallTest_Rule::class;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCallTest_Rule implements RuleContract {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return false;
    }

    public function message(): string {
        return 'validation.rule.isValid';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCallTest_LaravelDirective extends LaravelRule {
    public static function definition(): string {
        return 'directive @isValidLaravel';
    }

    protected function getRuleName(): string {
        return 'required_without:b.*.a';
    }
}
