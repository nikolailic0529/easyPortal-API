<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Location;
use App\Services\Organization\CurrentOrganization;
use App\Utils\ModelHelper;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\LogicalOperator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Utils;

use function array_filter;
use function array_flip;
use function array_is_list;
use function array_unique;
use function array_values;
use function explode;
use function is_a;
use function max;
use function sprintf;
use function strlen;

class Map {
    public function __construct(
        protected Container $container,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection {
        $diff           = max($args['diff'] ?? 0, 0.000000000001);
        $model          = new Location();
        $customer       = new Customer();
        $baseQuery      = $resolveInfo->argumentSet->enhanceBuilder(Location::query(), [])
            ->selectRaw("ROUND({$model->qualifyColumn('latitude')} / ?) as latitude_group", [$diff])
            ->selectRaw("ROUND({$model->qualifyColumn('longitude')} / ?) as longitude_group", [$diff])
            ->whereNotNull($model->qualifyColumn('latitude'))
            ->whereNotNull($model->qualifyColumn('longitude'))
            ->where(static function (EloquentBuilder $builder) use ($model): void {
                $builder->orWhere($model->qualifyColumn('customers_count'), '>', 0);
                $builder->orWhere($model->qualifyColumn('assets_count'), '>', 0);
            })
            ->groupBy('latitude_group')
            ->groupBy('longitude_group');
        $assetsQuery    = (clone $baseQuery)
            ->selectRaw("AVG({$model->qualifyColumn('latitude')}) as latitude_avg")
            ->selectRaw("MIN({$model->qualifyColumn('latitude')}) as latitude_min")
            ->selectRaw("MAX({$model->qualifyColumn('latitude')}) as latitude_max")
            ->selectRaw("AVG({$model->qualifyColumn('longitude')}) as longitude_avg")
            ->selectRaw("MIN({$model->qualifyColumn('longitude')}) as longitude_min")
            ->selectRaw("MAX({$model->qualifyColumn('longitude')}) as longitude_max")
            ->selectRaw("GROUP_CONCAT(DISTINCT {$model->getQualifiedKeyName()}, ',') as locations_ids")
            ->when($this->organization->isRoot(), static function (EloquentBuilder $builder) use ($model): void {
                $builder->selectRaw("SUM({$model->qualifyColumn('assets_count')}) as assets_count");
            })
            ->when(!$this->organization->isRoot(), function (EloquentBuilder $builder): void {
                $this->joinRelation(
                    $builder,
                    'resellers',
                    'r',
                    function (BelongsToMany $relation, EloquentBuilder $builder): EloquentBuilder {
                        return $builder
                            ->selectRaw($relation->getQualifiedRelatedKeyName())
                            ->selectRaw($relation->getQualifiedForeignPivotKeyName())
                            ->selectRaw($relation->qualifyPivotColumn('assets_count'))
                            ->where('reseller_id', '=', $this->organization->getKey());
                    },
                );

                $builder->selectRaw('IFNULL(SUM(r.assets_count), 0) as assets_count');
            });
        $customersQuery = (clone $baseQuery)
            ->selectRaw("COUNT(DISTINCT c.{$customer->getKeyName()}) as customers_count")
            ->selectRaw("GROUP_CONCAT(DISTINCT c.{$customer->getKeyName()}, ',') as customers_ids")
            ->when(true, function (EloquentBuilder $builder) use ($args, $resolveInfo): void {
                $this->joinRelation(
                    $builder,
                    'customers',
                    'c',
                    function (
                        BelongsToMany $relation,
                        EloquentBuilder $builder,
                    ) use (
                        $args,
                        $resolveInfo,
                    ): EloquentBuilder {
                        return $this
                            ->applySearchByConditions($builder, 'customers', $args, $resolveInfo)
                            ->selectRaw($relation->getQualifiedRelatedKeyName())
                            ->selectRaw($relation->getQualifiedForeignPivotKeyName());
                    },
                );
            });
        $locations      = $model->getConnection()->query()
            ->addSelect('assets.*')
            ->addSelect('customers.customers_count')
            ->addSelect('customers.customers_ids')
            ->fromSub($assetsQuery, 'assets')
            ->leftJoinSub($customersQuery, 'customers', static function (JoinClause $join): void {
                $join->on('assets.latitude_group', '=', 'customers.latitude_group');
                $join->on('assets.longitude_group', '=', 'customers.longitude_group');
            })
            ->where(static function (QueryBuilder $builder): void {
                $builder->orWhere('customers.customers_count', '>', 0);
                $builder->orWhere('assets.assets_count', '>', 0);
            })
            ->orderBy('assets.latitude_group')
            ->orderBy('assets.longitude_group')
            ->limit(1000)
            ->get();

        foreach ($locations as $location) {
            $location->customers_ids = $this->parseKeys($location->customers_ids ?? null);
            $location->locations_ids = $this->parseKeys($location->locations_ids ?? null);
        }

        return $locations;
    }

    protected function joinRelation(
        EloquentBuilder $builder,
        string $relation,
        string $alias,
        Closure $callback,
    ): EloquentBuilder {
        $relation = (new ModelHelper($builder))->getRelation($relation);

        if ($relation instanceof BelongsToMany) {
            $builder = $builder->leftJoinSub(
                $callback($relation, $relation->getQuery()),
                $alias,
                "{$alias}.{$relation->getForeignPivotKeyName()}",
                '=',
                $relation->getQualifiedParentKeyName(),
            );
        } else {
            throw new InvalidArgumentException(sprintf(
                'Relation `%s` not supported',
                $relation::class,
            ));
        }

        return $builder;
    }

    /**
     * @return array<string>
     */
    protected function parseKeys(?string $keys): array {
        return array_values(array_unique(array_filter(explode(',', (string) $keys), static function ($id) {
            return strlen($id) === 36;
        })));
    }

    /**
     * @param array<mixed> $args
     */
    protected function applySearchByConditions(
        Builder $builder,
        string $field,
        array $args,
        ResolveInfo $resolveInfo,
    ): Builder {
        foreach ($resolveInfo->argumentSet->arguments as $name => $argument) {
            // Search arg(s) with SearchBy directive
            $directives = $argument->directives->filter(
                Utils::instanceofMatcher(SearchByDirective::class),
            );

            if ($directives->isEmpty() || !isset($args[$name])) {
                continue;
            }

            // Cleanup and Apply conditions
            foreach ($directives as $directive) {
                /** @var \LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective $directive */
                $fields  = $this->getSearchByLogicalOperatorFields($directive);
                $where   = $this->getSearchByWhere($field, $fields, $args[$name]);
                $builder = $directive->handleBuilder($builder, $where);
            }
        }

        return $builder;
    }

    /**
     * @return array<string>
     */
    private function getSearchByLogicalOperatorFields(SearchByDirective $directive): array {
        return (new class($this->container, $directive) extends SearchByDirective {
            public function __construct(
                Container $container,
                protected SearchByDirective $directive,
            ) {
                parent::__construct($container);
            }

            /**
             * @return array<string>
             */
            public function getLogicalOperatorFields(): array {
                $operators = $this->directive->directiveArgValue(self::ArgOperators);
                $operators = (new Collection($operators))
                    ->map(function (string $operator): ?string {
                        return is_a($operator, LogicalOperator::class, true)
                            ? $this->container->make($operator)->getName()
                            : null;
                    })
                    ->filter()
                    ->all();

                return $operators;
            }
        })->getLogicalOperatorFields();
    }

    /**
     * @param array<string> $fields
     * @param array<mixed>  $where
     *
     * @return array<mixed>
     */
    protected function getSearchByWhere(string $field, array $fields, array $where): array {
        $clean     = [];
        $operators = array_flip($fields);

        if (array_is_list($where)) {
            foreach ($where as $value) {
                $value = $this->getSearchByWhere($field, $fields, $value);

                if ($value) {
                    $clean[] = $value;
                }
            }
        } else {
            foreach ($where as $key => $value) {
                if (isset($operators[$key])) {
                    $clean[$key] = $this->getSearchByWhere($field, $fields, $value);
                } elseif ($key === $field && isset($value['where']) && $value['where']) {
                    $clean += $value['where'];
                } else {
                    continue;
                }
            }
        }

        return $clean;
    }
}
