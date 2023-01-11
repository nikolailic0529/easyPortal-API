<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Complex;

use App\GraphQL\Extensions\Lighthouse\DirectiveLocator;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use LastDragon_ru\LaraASP\Eloquent\Enum;
use LastDragon_ru\LaraASP\Eloquent\Exceptions\PropertyIsNotRelation;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\Client\ConditionTooManyOperators;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Directives\Directive;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithQueryLogs;

use function is_array;

/**
 * @internal
 * @covers \App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Complex\Relation
 *
 * @phpstan-import-type BuilderFactory from BuilderDataProvider
 */
class RelationTest extends TestCase {
    use WithQueryLogs;
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderCall
     *
     * @param array{query: string, bindings: array<mixed>}|Exception $expected
     * @param BuilderFactory                                         $builderFactory
     * @param Closure(static): Argument                              $argumentFactory
     * @param Closure(static): void                                  $prepare
     */
    public function testCall(
        array|Exception $expected,
        mixed $builderFactory,
        Property $property,
        Closure $argumentFactory,
        Closure $prepare = null,
    ): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        if ($prepare) {
            $prepare($this);
        }

        $operator = $this->app->make(Relation::class);
        $argument = $argumentFactory($this);
        $search   = $this->app->make(Directive::class);
        $builder  = $builderFactory($this);
        $builder  = $operator->call($search, $builder, $property, $argument);

        if (is_array($expected)) {
            self::assertDatabaseQueryEquals($expected, $builder);
        } else {
            self::fail('Something wrong...');
        }
    }

    /**
     * @coversNothing
     */
    public function testIntegration(): void {
        $actual   = $this->app->make(DirectiveLocator::class)->create('searchByOperatorRelation');
        $expected = Relation::class;

        self::assertInstanceOf($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCall(): array {
        $prepare = static function (): void {
            EloquentRelation::morphMap([
                'a' => RelationTest__ModelA::class,
                'b' => RelationTest__ModelB::class,
            ]);
        };
        $graphql = <<<'GRAPHQL'
            input TestInput {
                property: TestOperators
                @searchByOperatorProperty

                parent: TestRelation
                @searchByOperatorRelation
            }

            input TestOperators {
                lessThan: Int
                @searchByOperatorLessThan

                equal: Int
                @searchByOperatorEqual

                notEqual: Int
                @searchByOperatorNotEqual
            }

            input TestRelation {
                where: TestInput

                count: TestOperators

                exists: Boolean

                notExists: Boolean! = false
            }

            type Query {
                test(input: TestInput): Int @all
            }
        GRAPHQL;

        return [
            'not a relation'                                              => [
                new PropertyIsNotRelation(new RelationTest__ModelA(), 'delete'),
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('delete'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation!',
                        [
                            'notExists' => true,
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'HasMany: {exists: true}'                                     => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            `table_a`.`id` in (
                                select
                                    distinct `table_b`.`table_a_id`
                                from
                                    `table_b`
                            )
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('child'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation!',
                        [
                            'exists' => true,
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'MorphTo: {exists: true}'                                     => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            (
                                (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` in (
                                        select
                                            distinct `table_a`.`id`
                                        from
                                            `table_a`
                                    )
                                )
                                or (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` in (
                                        select
                                            distinct `table_b`.`id`
                                        from
                                            `table_b`
                                    )
                                )
                            )
                    SQL
                    ,
                    'bindings' => [
                        'a',
                        'b',
                    ],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('object'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation!',
                        [
                            'exists' => true,
                        ],
                        $graphql,
                    );
                },
                $prepare,
            ],
            'HasManyDeep: {exists: true}'                                 => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            exists (
                                select
                                    *
                                from
                                    `table_b`
                                    inner join `table_a` as `laravel_reserved_0`
                                        on `laravel_reserved_0`.`id` = `table_b`.`relation_test___model_a_id`
                                    inner join `table_b`
                                        on `table_b`.`id` = `laravel_reserved_0`.`relation_test___model_b_id`
                                where
                                    `table_a`.`id` = `table_b`.`relation_test___model_a_id`
                            )
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('children'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation!',
                        [
                            'exists' => true,
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'HasMany: {notExists: true}'                                  => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            `table_a`.`id` not in (
                                select
                                    distinct `table_b`.`table_a_id`
                                from
                                    `table_b`
                            )
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('child'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation!',
                        [
                            'notExists' => true,
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'MorphTo: {notExists: true}'                                  => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            (
                                (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` not in (
                                        select
                                            distinct `table_a`.`id`
                                        from
                                            `table_a`
                                    )
                                )
                                or (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` not in (
                                        select
                                            distinct `table_b`.`id`
                                        from
                                            `table_b`
                                    )
                                )
                            )
                    SQL
                    ,
                    'bindings' => [
                        'a',
                        'b',
                    ],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('object'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation!',
                        [
                            'notExists' => true,
                        ],
                        $graphql,
                    );
                },
                $prepare,
            ],
            'HasManyDeep: {notExists: true}'                              => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            not exists (
                                select
                                    *
                                from
                                    `table_b`
                                    inner join `table_a` as `laravel_reserved_0`
                                        on `laravel_reserved_0`.`id` = `table_b`.`relation_test___model_a_id`
                                    inner join `table_b`
                                        on `table_b`.`id` = `laravel_reserved_0`.`relation_test___model_b_id`
                                where
                                    `table_a`.`id` = `table_b`.`relation_test___model_a_id`
                            )
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('children'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation!',
                        [
                            'notExists' => true,
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'HasMany: {relation: {property: {equal: 1}}}'                 => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            `table_a`.`id` in (
                                select
                                    distinct `table_b`.`table_a_id`
                                from
                                    `table_b`
                                where
                                    `table_b`.`property` = ?
                            )
                    SQL
                    ,
                    'bindings' => [
                        123,
                    ],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('child'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'where' => [
                                'property' => [
                                    'equal' => 123,
                                ],
                            ],
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'MorphTo: {relation: {property: {equal: 1}}}'                 => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            (
                                (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` in (
                                        select
                                            distinct `table_a`.`id`
                                        from
                                            `table_a`
                                        where
                                            `table_a`.`property` = ?
                                    )
                                )
                                or (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` in (
                                        select
                                            distinct `table_b`.`id`
                                        from
                                            `table_b`
                                        where
                                            `table_b`.`property` = ?
                                    )
                                )
                            )
                    SQL
                    ,
                    'bindings' => [
                        'a',
                        123,
                        'b',
                        123,
                    ],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('object'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'where' => [
                                'property' => [
                                    'equal' => 123,
                                ],
                            ],
                        ],
                        $graphql,
                    );
                },
                $prepare,
            ],
            'HasManyDeep: {relation: {property: {equal: 1}}}'             => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            exists (
                                select
                                    *
                                from
                                    `table_b`
                                    inner join `table_a` as `laravel_reserved_0`
                                        on `laravel_reserved_0`.`id` = `table_b`.`relation_test___model_a_id`
                                    inner join `table_b`
                                        on `table_b`.`id` = `laravel_reserved_0`.`relation_test___model_b_id`
                                where
                                    `table_a`.`id` = `table_b`.`relation_test___model_a_id`
                                    and `laravel_reserved_0`.`property` = ?
                            )
                    SQL
                    ,
                    'bindings' => [
                        123,
                    ],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('children'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'where' => [
                                'property' => [
                                    'equal' => 123,
                                ],
                            ],
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'HasMany: {count: {equal: 1}}'                                => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            (
                                select
                                    count(*)
                                from
                                    `table_b`
                                where
                                    `table_a`.`id` = `table_b`.`table_a_id`
                            ) = 345
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('child'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'count' => [
                                'equal' => 345,
                            ],
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'MorphTo: {count: {equal: 1}}'                                => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            (
                                (
                                    `table_a`.`object_type` = ?
                                    and (
                                        select
                                            count(*)
                                        from
                                            `table_a` as `laravel_reserved_0`
                                        where
                                            `laravel_reserved_0`.`id` = `table_a`.`object_id`
                                    ) = 345
                                )
                                or (
                                    `table_a`.`object_type` = ?
                                    and (
                                        select
                                            count(*)
                                        from
                                            `table_b`
                                        where
                                            `table_a`.`object_id` = `table_b`.`id`
                                    ) = 345
                                )
                            )
                    SQL
                    ,
                    'bindings' => [
                        'a',
                        'b',
                    ],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('object'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'count' => [
                                'equal' => 345,
                            ],
                        ],
                        $graphql,
                    );
                },
                $prepare,
            ],
            'HasManyDeep: {count: {equal: 1}}'                            => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            (
                                select
                                    count(*)
                                from
                                    `table_b`
                                    inner join `table_a` as `laravel_reserved_0`
                                        on `laravel_reserved_0`.`id` = `table_b`.`relation_test___model_a_id`
                                    inner join `table_b`
                                        on `table_b`.`id` = `laravel_reserved_0`.`relation_test___model_b_id`
                                where
                                    `table_a`.`id` = `table_b`.`relation_test___model_a_id`
                            ) = 345
                    SQL
                    ,
                    'bindings' => [],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('children'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'count' => [
                                'equal' => 345,
                            ],
                        ],
                        $graphql,
                    );
                },
                null,
            ],
            'HasMany: {relation: {relation: {property: {equal: 1}}}}'     => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            `table_a`.`id` in (
                                select
                                    distinct `table_b`.`table_a_id`
                                from
                                    `table_b`
                                where
                                    `table_b`.`parent_id` in (
                                        select
                                            distinct `table_a`.`id`
                                        from
                                            `table_a`
                                        where
                                            `table_a`.`property` = ?
                                    )
                            )
                    SQL
                    ,
                    'bindings' => [123],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('child'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'where' => [
                                'parent' => [
                                    'where' => [
                                        'property' => [
                                            'equal' => 123,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        $graphql,
                    );
                },
            ],
            'MorphTo: {relation: {relation: {property: {equal: 1}}}}'     => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            (
                                (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` in (
                                        select
                                            distinct `table_a`.`id`
                                        from
                                            `table_a`
                                        where
                                            `table_a`.`parent_id` in (
                                                select
                                                    distinct `table_a`.`id`
                                                from
                                                    `table_a`
                                                where
                                                    `table_a`.`property` = ?
                                            )
                                    )
                                )
                                or (
                                    `table_a`.`object_type` = ?
                                    and `table_a`.`object_id` in (
                                        select
                                            distinct `table_b`.`id`
                                        from
                                            `table_b`
                                        where
                                            `table_b`.`parent_id` in (
                                                select
                                                    distinct `table_a`.`id`
                                                from
                                                    `table_a`
                                                where
                                                    `table_a`.`property` = ?
                                            )
                                    )
                                )
                            )
                    SQL
                    ,
                    'bindings' => [
                        'a',
                        123,
                        'b',
                        123,
                    ],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('object'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'where' => [
                                'parent' => [
                                    'where' => [
                                        'property' => [
                                            'equal' => 123,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        $graphql,
                    );
                },
                $prepare,
            ],
            'HasManyDeep: {relation: {relation: {property: {equal: 1}}}}' => [
                [
                    'query'    => <<<'SQL'
                        select
                            *
                        from
                            `table_a`
                        where
                            exists (
                                select
                                    *
                                from
                                    `table_b`
                                    inner join `table_a` as `laravel_reserved_0`
                                        on `laravel_reserved_0`.`id` = `table_b`.`relation_test___model_a_id`
                                    inner join `table_b`
                                        on `table_b`.`id` = `laravel_reserved_0`.`relation_test___model_b_id`
                                where
                                    `table_a`.`id` = `table_b`.`relation_test___model_a_id`
                                    and `table_b`.`parent_id` in (
                                        select
                                            distinct `table_a`.`id`
                                        from
                                            `table_a`
                                        where
                                            `table_a`.`property` = ?
                                    )
                            )
                    SQL
                    ,
                    'bindings' => [123],
                ],
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('children'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'where' => [
                                'parent' => [
                                    'where' => [
                                        'property' => [
                                            'equal' => 123,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        $graphql,
                    );
                },
            ],
            '{count: { multiple operators }}'                             => [
                new ConditionTooManyOperators(['lessThan', 'equal']),
                static function (): EloquentBuilder {
                    return RelationTest__ModelA::query();
                },
                new Property('children'),
                static function (self $test) use ($graphql): Argument {
                    return $test->getGraphQLArgument(
                        'TestRelation',
                        [
                            'count' => [
                                'equal'    => 345,
                                'lessThan' => 1,
                            ],
                        ],
                        $graphql,
                    );
                },
                null,
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
class RelationTest__ModelA extends Model {
    use HasRelationships;
    use HasTableAlias;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    public $table = 'table_a';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string, string|class-string>
     */
    public $casts = [
        'object_type' => RelationTest__Enum::class,
    ];

    /**
     * @return HasOne<RelationTest__ModelB>
     */
    public function child(): HasOne {
        return $this->hasOne(RelationTest__ModelB::class, 'table_a_id');
    }

    /**
     * @return MorphTo<Model, RelationTest__ModelA>
     */
    public function object(): MorphTo {
        return $this->morphTo('object');
    }

    /**
     * @return HasManyDeep<RelationTest__ModelB>
     */
    public function children(): HasManyDeep {
        return $this->hasManyDeep(
            RelationTest__ModelB::class,
            [
                RelationTest__ModelB::class,
                RelationTest__ModelA::class,
            ],
        );
    }

    /**
     * @return BelongsTo<RelationTest__ModelA, self>
     */
    public function parent(): BelongsTo {
        return $this->belongsTo(RelationTest__ModelA::class);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class RelationTest__ModelB extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    public $table = 'table_b';

    /**
     * @return BelongsTo<RelationTest__ModelA, self>
     */
    public function parent(): BelongsTo {
        return $this->belongsTo(RelationTest__ModelA::class);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class RelationTest__Enum extends Enum {
    public static function a(): static {
        return static::make(__FUNCTION__);
    }

    public static function b(): static {
        return static::make(__FUNCTION__);
    }
}
