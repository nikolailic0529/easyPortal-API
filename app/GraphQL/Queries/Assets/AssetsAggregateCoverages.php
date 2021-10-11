<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Coverage;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AssetsAggregateCoverages extends AggregateResolver {
    protected function getQuery(): DatabaseBuilder|EloquentBuilder {
        return Coverage::query();
    }

    protected function enhanceBuilder(
        EloquentBuilder|DatabaseBuilder $builder,
        mixed $root,
        ?array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): DatabaseBuilder|EloquentBuilder {
        $coverage = $builder->getModel();

        return $builder
            ->selectRaw($coverage->qualifyColumn('*'))
            ->selectRaw('COUNT(a.`asset_id`) as count')
            ->joinRelation(
                'assets',
                'a',
                function (
                    BelongsToMany $relation,
                    EloquentBuilder $builder,
                ) use (
                    $root,
                    $args,
                    $context,
                    $resolveInfo,
                ): EloquentBuilder {
                    return parent::enhanceBuilder($builder, $root, $args, $context, $resolveInfo)
                        ->selectRaw("{$builder->getModel()->getQualifiedKeyName()} as `asset_id`")
                        ->selectRaw($relation->getQualifiedForeignPivotKeyName());
                },
            )
            ->groupBy($coverage->getQualifiedKeyName())
            ->having('count', '>', 0);
    }

    protected function getResult(EloquentBuilder|DatabaseBuilder $builder): mixed {
        $results   = $builder->get();
        $aggregate = [];

        foreach ($results as $result) {
            $aggregate[] = [
                'count'       => $result->count,
                'coverage_id' => $result->getKey(),
                'coverage'    => $result,
            ];
        }

        return $aggregate;
    }
}
