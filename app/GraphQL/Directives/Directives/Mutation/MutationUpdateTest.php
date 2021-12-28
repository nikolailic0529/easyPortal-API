<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Mockery;
use Mockery\MockInterface;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutOrganizationScope;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Mutation\MutationUpdate
 */
class MutationUpdateTest extends TestCase {
    use WithoutOrganizationScope;
    use WithGraphQLSchema;

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldModelNotDefined(): void {
        $mutation = json_encode(MutationUpdateTest_Mutation::class);
        $builder  = json_encode(MutationUpdateTest_Builder::class);

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
                    @mutationUpdate(
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
            ->assertThat(new GraphQLError('model', new ObjectNotFound()));
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldModelDefined(): void {
        $customer = Customer::factory()->create();
        $mutation = json_encode(MutationUpdateTest_Mutation::class);
        $builder  = json_encode(MutationUpdateTest_Builder::class);

        $this->override(
            MutationUpdateTest_Mutation::class,
            static function (MockInterface $mock) use ($customer): void {
                $mock
                    ->shouldReceive('__invoke')
                    ->withArgs(static function (Customer $c) use ($customer): bool {
                        return $customer->is($c);
                    })
                    ->once()
                    ->andReturn($customer->getKey());
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
                    model(id: ID! @eq): ModelMutations!
                    @mutation(
                        builder: {$builder},
                    )
                }

                type ModelMutations {
                    call: String
                    @mutationUpdate(
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
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationUpdateTest_Builder {
    public function __invoke(): EloquentBuilder {
        return Customer::query();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class MutationUpdateTest_Mutation {
    public function __invoke(): mixed {
        return null;
    }
}
