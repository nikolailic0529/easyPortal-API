<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\GraphQL\Resolvers\AggregateResolver;
use App\Models\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AssetsAggregateTypes extends AggregateResolver {
    protected function getQuery(): DatabaseBuilder|EloquentBuilder {
        return Type::query();
    }

    protected function enhanceBuilder(
        EloquentBuilder|DatabaseBuilder $builder,
        mixed $root,
        ?array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): DatabaseBuilder|EloquentBuilder {
        $type = $builder->getModel();

        return $builder
            ->selectRaw($type->qualifyColumn('*'))
            ->selectRaw('SUM(a.`count`) as count')
            ->joinRelation(
                'assets',
                'a',
                function (
                    HasMany $relation,
                    EloquentBuilder $builder,
                ) use (
                    $root,
                    $args,
                    $context,
                    $resolveInfo,
                ): EloquentBuilder {
                    return parent::enhanceBuilder($builder, $root, $args, $context, $resolveInfo)
                        ->selectRaw("COUNT({$builder->getModel()->getQualifiedKeyName()}) as `count`")
                        ->selectRaw($relation->getQualifiedForeignKeyName())
                        ->groupBy($relation->getQualifiedForeignKeyName());
                },
            )
            ->groupBy($type->getQualifiedKeyName())
            ->having('count', '>', 0);
    }

    protected function getResult(EloquentBuilder|DatabaseBuilder $builder): mixed {
        $results   = $builder->get();
        $aggregate = [];

        foreach ($results as $result) {
            /** @var \App\Models\Type $result */
            $aggregate[] = [
                'count'   => $result->count,
                'type_id' => $result->getKey(),
                'type'    => $result,
            ];
        }

        return $aggregate;
    }
}
