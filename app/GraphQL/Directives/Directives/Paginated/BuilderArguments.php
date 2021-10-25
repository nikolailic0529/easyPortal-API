<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * @mixin \Nuwave\Lighthouse\Schema\Directives\BaseDirective
 */
trait BuilderArguments {
    protected static function getArgumentsDefinition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Specify the class name of the model to use.
            This is only needed when the default model detection does not work.
            """
            model: String

            """
            Point to a function that provides a Query Builder instance.
            This replaces the use of a model.
            """
            builder: String
            GRAPHQL;
    }

    /**
     * @param array<mixed> $args
     */
    protected function getBuilder(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): EloquentBuilder|QueryBuilder {
        if ($this->directiveHasArgument('builder')) {
            $resolver = $this->getResolverFromArgument('builder');
            $query    = $resolver($root, $args, $context, $resolveInfo);
        } else {
            $query = $this->getModelClass()::query();
        }

        return $resolveInfo->argumentSet->enhanceBuilder($query, []);
    }
}
