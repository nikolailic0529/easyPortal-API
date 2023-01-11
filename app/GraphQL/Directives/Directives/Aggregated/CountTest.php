<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Services\Search\Builders\Builder as SearchBuilder;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutGlobalScopes;
use Tests\WithQueryLogs;
use Tests\WithSearch;

use function json_encode;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Aggregated\Count
 */
class CountTest extends TestCase {
    use WithoutGlobalScopes;
    use WithGraphQLSchema;
    use WithSearch;
    use WithQueryLogs;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderResolveField
     *
     * @param Closure(static):EloquentBuilder<Model>|Closure(static):QueryBuilder $builder
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
            ->assertThat(new GraphQLSuccess('data', [
                'count' => $expected,
            ]));
    }

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
            ->assertThat(new GraphQLSuccess('data', [
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
            QueryBuilder::class                 => [
                1,
                static function (): QueryBuilder {
                    Customer::factory()->create();

                    return Customer::query()->toBase();
                },
            ],
            QueryBuilder::class.'(group by)'    => [
                1,
                static function (self $test): QueryBuilder {
                    Customer::factory()->count(2)->create([
                        'name' => $test->faker->company(),
                    ]);

                    return Customer::query()
                        ->select('name')
                        ->groupBy('name')
                        ->toBase();
                },
            ],
            EloquentBuilder::class              => [
                2,
                static function (): EloquentBuilder {
                    Customer::factory()->count(2)->create();

                    return Customer::query();
                },
            ],
            EloquentBuilder::class.'(group by)' => [
                1,
                static function (self $test): EloquentBuilder {
                    Customer::factory()->count(2)->create([
                        'name' => $test->faker->company(),
                    ]);

                    return Customer::query()
                        ->select('name')
                        ->groupBy('name');
                },
            ],
            EloquentBuilder::class.'(distinct)' => [
                1,
                static function (self $test): EloquentBuilder {
                    $asset    = Asset::factory()->create();
                    $document = Document::factory()->create();

                    DocumentEntry::factory()->create([
                        'asset_id'    => $asset,
                        'document_id' => $document,
                    ]);
                    DocumentEntry::factory()->create([
                        'asset_id'    => $asset,
                        'document_id' => $document,
                    ]);

                    return $asset->documents()->getQuery();
                },
            ],
            SearchBuilder::class                => [
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
