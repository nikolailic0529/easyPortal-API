<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_filter;
use function assert;
use function implode;
use function sprintf;

/**
 * @mixin BaseDirective
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
     *
     * @return EloquentBuilder<Model>|QueryBuilder|SearchBuilder<Model>
     */
    protected function getBuilder(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): EloquentBuilder|QueryBuilder|SearchBuilder {
        if (!$this->allowGuessBuilder()) {
            $required  = ['builder', 'model', 'relation'];
            $arguments = array_filter($required, function (string $argument): bool {
                return $this->directiveHasArgument($argument);
            });

            if (!$arguments) {
                throw new InvalidArgumentException(sprintf(
                    'At least one of `%s` argument required.',
                    implode('`, `', $required),
                ));
            }
        }

        $query = null;

        if ($this->directiveHasArgument('relation')) {
            if (!($root instanceof Model)) {
                throw new InvalidArgumentException(
                    'The `relation` can be used only when root is the model.',
                );
            }

            $query = (new ModelHelper($root))
                ->getRelation($this->directiveArgValue('relation'))
                ->getQuery();
        } elseif ($this->directiveHasArgument('builder')) {
            $resolver = $this->getResolverFromArgument('builder');
            $query    = $resolver($root, $args, $context, $resolveInfo);
        } else {
            $query = $this->getModelClass()::query();
        }

        $query = $resolveInfo->argumentSet->enhanceBuilder($query, []);

        assert(
            $query instanceof EloquentBuilder || $query instanceof QueryBuilder || $query instanceof SearchBuilder,
        );

        return $query;
    }

    protected function allowGuessBuilder(): bool {
        return true;
    }

    /**
     * @return array<string>
     */
    public function getBuilderArguments(): array {
        $arguments = [];

        if ($this->directiveHasArgument('builder')) {
            $arguments['builder'] = $this->directiveArgValue('builder');
        } else {
            $arguments['model'] = $this->getModelClass();
        }

        return $arguments;
    }
}
