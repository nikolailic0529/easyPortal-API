<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\Models\Customer;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Constraint\Constraint;
use stdClass;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutOrganizationScope;
use Tests\WithSearch;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Aggregated\Aggregated
 */
class AggregatedTest extends TestCase {
    use WithoutOrganizationScope;
    use WithGraphQLSchema;
    use WithSearch;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     *
     * @param \Closure(): array<string, mixed> $arguments
     */
    public function testResolveField(Constraint $expected, Closure $arguments): void {
        $arguments = (new Collection($arguments()))
            ->map(static function (mixed $value, string $key): string {
                return $key.': '.json_encode($value);
            })
            ->implode(',');
        $arguments = $arguments ? "({$arguments})" : '';

        if ($arguments) {
            $this->mockResolver(static function (BuilderValue $value): ?string {
                $builder = $value->getBuilder();
                $key     = null;

                if ($builder instanceof EloquentBuilder) {
                    $key = $builder->get()->first()?->getKey();
                } elseif ($builder instanceof QueryBuilder) {
                    $key = $builder->get()->first()?->id ?? null;
                } else {
                    // empty
                }

                return $key;
            });
        } else {
            $this->mockResolverExpects(
                $this->never(),
            );
        }

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    data: Data! @aggregated{$arguments}
                }

                type Data {
                    value: String @mock
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
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderResolveField(): array {
        return [
            'empty'           => [
                new GraphQLError('data', new InvalidArgumentException(
                    'At least one of `builder`, `model` argument required.',
                )),
                static function (): array {
                    return [];
                },
            ],
            'model'           => [
                new GraphQLSuccess('data', null, [
                    'value' => '3bb32fc9-55ea-437d-b307-278e19a48cd4',
                ]),
                static function (): array {
                    Customer::factory()->create([
                        'id' => '3bb32fc9-55ea-437d-b307-278e19a48cd4',
                    ]);

                    return [
                        'model' => Customer::class,
                    ];
                },
            ],
            'builder'         => [
                new GraphQLSuccess('data', null, [
                    'value' => '7a2cd1d4-91df-4ba8-bd69-2a172920ec81',
                ]),
                static function (): array {
                    Customer::factory()->create([
                        'id' => '7a2cd1d4-91df-4ba8-bd69-2a172920ec81',
                    ]);

                    return [
                        'builder' => AggregatedTest_Resolver::class,
                    ];
                },
            ],
            'model + builder' => [
                new GraphQLSuccess('data', null, [
                    'value' => '25dcdac0-3f19-4f6b-8995-3f6272cb655d',
                ]),
                static function (): array {
                    Customer::factory()->create([
                        'id' => '25dcdac0-3f19-4f6b-8995-3f6272cb655d',
                    ]);

                    return [
                        'builder' => AggregatedTest_Resolver::class,
                        'model'   => stdClass::class,
                    ];
                },
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class AggregatedTest_Resolver {
    public function __invoke(): QueryBuilder {
        return Customer::query()->toBase();
    }
}
