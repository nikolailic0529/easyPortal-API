<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\Models\Customer;
use App\Services\Search\Builders\Builder as SearchBuilder;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutOrganizationScope;
use Tests\WithSearch;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Aggregated\AggregatedCount
 */
class AggregatedCountTest extends TestCase {
    use WithoutOrganizationScope;
    use WithGraphQLSchema;
    use WithSearch;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     */
    public function testResolveField(int $expected, Closure $builder): void {
        $context     = Mockery::mock(GraphQLContext::class);
        $resolveInfo = Mockery::mock(ResolveInfo::class);

        $this->mockResolver('data')->willReturn(
            new BuilderValue(null, [], $context, $resolveInfo, $builder($this)),
        );

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                type Query {
                    data: Data! @mock
                }

                type Data {
                    count: Int! @aggregatedCount
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    data {
                        count
                    }
                }
                GRAPHQL,
            )
            ->assertThat(new GraphQLSuccess('data', null, [
                'count' => $expected,
            ]));
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldScout(): void {
        $model = Customer::factory()->create();
        $class = json_encode($model::class);

        $this->makeSearchable($model);

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    data(search: String @search): Data! @aggregated(model: {$class})
                }

                type Data {
                    value: Int! @aggregatedCount
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    data {
                        value
                    }
                }
                GRAPHQL,
            )
            ->assertThat(new GraphQLSuccess('data', null, [
                'value' => 1,
            ]));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderResolveField(): array {
        return [
            QueryBuilder::class    => [
                1,
                static function (): QueryBuilder {
                    Customer::factory()->create();

                    return Customer::query()->toBase();
                },
            ],
            EloquentBuilder::class => [
                2,
                static function (): EloquentBuilder {
                    Customer::factory()->count(2)->create();

                    return Customer::query();
                },
            ],
            SearchBuilder::class   => [
                2,
                static function (self $test): SearchBuilder {
                    $test->makeSearchable(
                        Customer::factory()->count(2)->make(),
                    );

                    return Customer::search('*');
                },
            ],
        ];
    }
    // </editor-fold>
}
