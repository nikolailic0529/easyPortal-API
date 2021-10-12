<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

use App\Models\Customer;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

abstract class AggregateResolver {
    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): mixed {
        $query  = $this->getQuery();
        $query  = $this->enhanceBuilder($query, $root, $args, $context, $resolveInfo);
        $result = $this->getResult($query);

        return $result;
    }

    protected function getResult(DatabaseBuilder|EloquentBuilder $builder): mixed {
        if ($builder instanceof EloquentBuilder) {
            $builder = $builder->toBase();
        }

        return $builder->first();
    }

    /**
     * @param array<string,mixed>|null $args
     */
    protected function enhanceBuilder(
        DatabaseBuilder|EloquentBuilder $builder,
        mixed $root,
        ?array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): DatabaseBuilder|EloquentBuilder {
        // Conditions
        $builder = $resolveInfo->argumentSet->enhanceBuilder($builder, []);

        // Unfortunately is not possible to get `$root` inside `@builder` directive.
        //
        // https://github.com/nuwave/lighthouse/issues/1736
        if ($root instanceof Customer) {
            $builder = $builder->where('customer_id', '=', $root->getKey());
        }

        // Return
        return $builder;
    }

    abstract protected function getQuery(): DatabaseBuilder|EloquentBuilder;
}
