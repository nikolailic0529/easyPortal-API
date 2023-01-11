<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\InvalidContext;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Mockery;
use Mockery\MockInterface;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutGlobalScopes;

use function json_encode;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Mutation\MutationCreate
 */
class MutationCreateTest extends TestCase {
    use WithoutGlobalScopes;
    use WithGraphQLSchema;

    public function testResolveFieldModelNotDefined(): void {
        $customer = $this->faker->uuid();
        $mutation = json_encode(MutationCreateTest_Mutation::class);
        $builder  = json_encode(MutationCreateTest_Builder::class);

        $this->override(
            MutationCreateTest_Mutation::class,
            static function (MockInterface $mock) use ($customer): void {
                $mock
                    ->shouldReceive('__invoke')
                    ->with(null, Mockery::andAnyOtherArgs())
                    ->once()
                    ->andReturn($customer);
            },
        );

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    mocked: String @mock
                }
                type Mutation {
                    model(id: ID @eq): ModelMutations!
                    @mutation(
                        builder: {$builder},
                    )
                }

                type ModelMutations {
                    call: String
                    @mutationCreate(
                        resolver: {$mutation},
                    )
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test {
                    model {
                        call
                    }
                }
                GRAPHQL,
            )
            ->assertThat(new OkResponse(AnySchema::class, [
                'data' => [
                    'model' => [
                        'call' => $customer,
                    ],
                ],
            ]));
    }

    public function testResolveFieldModelDefined(): void {
        $customer = Customer::factory()->create();
        $mutation = json_encode(MutationCreateTest_Mutation::class);
        $builder  = json_encode(MutationCreateTest_Builder::class);

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
                    @mutationCreate(
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
            ->assertThat(new GraphQLError('model', new InvalidContext()));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCreateTest_Builder {
    public function __invoke(): EloquentBuilder {
        return Customer::query();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationCreateTest_Mutation {
    public function __invoke(): mixed {
        return null;
    }
}
