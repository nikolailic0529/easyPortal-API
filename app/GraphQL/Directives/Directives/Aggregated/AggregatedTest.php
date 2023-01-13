<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use App\Models\Asset;
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
use Tests\WithoutGlobalScopes;
use Tests\WithSearch;

use function json_encode;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Aggregated\Aggregated
 */
class AggregatedTest extends TestCase {
    use WithoutGlobalScopes;
    use WithGraphQLSchema;
    use WithSearch;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderResolveField
     *
     * @param Closure(static): mixed          $root
     * @param Closure(): array<string, mixed> $arguments
     */
    public function testResolveField(Constraint $expected, Closure $root, Closure $arguments, bool $called): void {
        $arguments = (new Collection($arguments()))
            ->map(static function (mixed $value, string $key): string {
                return $key.': '.json_encode($value);
            })
            ->implode(',');
        $arguments = $arguments ? "({$arguments})" : '';

        $this->mockResolver(function () use ($root): mixed {
            return $root($this);
        }, 'root');

        if ($called) {
            $this->mockResolver(static function (BuilderValue $value): ?string {
                $builder = $value->getBuilder();
                $key     = null;

                if ($builder instanceof EloquentBuilder) {
                    $key = $builder->first()?->getKey();
                } elseif ($builder instanceof QueryBuilder) {
                    $key = $builder->first()?->id;
                } else {
                    // empty
                }

                return $key;
            });
        } else {
            $this->mockResolverExpects(
                self::never(),
            );
        }

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    root: Root! @mock(key: "root")
                }

                type Root {
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
                    root {
                        data {
                            value
                        }
                    }
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }

    public function testResolveFieldScout(): void {
        $model = Customer::factory()->create();
        $class = json_encode($model::class);

        $this->makeSearchable($model);
        $this->mockResolver(static function (BuilderValue $value): int {
            return $value->getBuilder()->count();
        });

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    data(search: String @search): Data! @aggregated(model: {$class})
                }

                type Data {
                    value: Int! @mock
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
            'empty'                  => [
                new GraphQLError('data', new InvalidArgumentException(
                    'At least one of `builder`, `model`, `relation` argument required.',
                )),
                static function (): object {
                    return new stdClass();
                },
                static function (): array {
                    return [];
                },
                false,
            ],
            'model'                  => [
                new GraphQLSuccess('root', [
                    'data' => [
                        'value' => '3bb32fc9-55ea-437d-b307-278e19a48cd4',
                    ],
                ]),
                static function (): object {
                    return new stdClass();
                },
                static function (): array {
                    Customer::factory()->create([
                        'id' => '3bb32fc9-55ea-437d-b307-278e19a48cd4',
                    ]);

                    return [
                        'model' => Customer::class,
                    ];
                },
                true,
            ],
            'builder'                => [
                new GraphQLSuccess('root', [
                    'data' => [
                        'value' => '7a2cd1d4-91df-4ba8-bd69-2a172920ec81',
                    ],
                ]),
                static function (): object {
                    return new stdClass();
                },
                static function (): array {
                    Customer::factory()->create([
                        'id' => '7a2cd1d4-91df-4ba8-bd69-2a172920ec81',
                    ]);

                    return [
                        'builder' => AggregatedTest_Resolver::class,
                    ];
                },
                true,
            ],
            'model + builder'        => [
                new GraphQLSuccess('root', [
                    'data' => [
                        'value' => '25dcdac0-3f19-4f6b-8995-3f6272cb655d',
                    ],
                ]),
                static function (): object {
                    return new stdClass();
                },
                static function (): array {
                    Customer::factory()->create([
                        'id' => '25dcdac0-3f19-4f6b-8995-3f6272cb655d',
                    ]);

                    return [
                        'builder' => AggregatedTest_Resolver::class,
                        'model'   => stdClass::class,
                    ];
                },
                true,
            ],
            'relation'               => [
                new GraphQLSuccess('root', [
                    'data' => [
                        'value' => 'd42d9884-a39b-4dfc-97bc-6785ef03f4f4',
                    ],
                ]),
                static function (): object {
                    return Asset::factory()->make([
                        'customer_id' => 'd42d9884-a39b-4dfc-97bc-6785ef03f4f4',
                    ]);
                },
                static function (): array {
                    Customer::factory()->create([
                        'id' => 'd42d9884-a39b-4dfc-97bc-6785ef03f4f4',
                    ]);

                    return [
                        'relation' => 'customer',
                        'builder'  => stdClass::class,
                        'model'    => stdClass::class,
                    ];
                },
                true,
            ],
            'relation without model' => [
                new GraphQLError('data', new InvalidArgumentException(
                    'The `relation` can be used only when root is the model.',
                )),
                static function (): object {
                    return new stdClass();
                },
                static function (): array {
                    return [
                        'relation' => 'customer',
                    ];
                },
                false,
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
