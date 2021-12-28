<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Utils\Eloquent\ModelHelper;
use Closure;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;
use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_filter;
use function count;
use function implode;
use function sprintf;

abstract class Mutation extends BaseDirective implements FieldResolver, FieldManipulator {
    public const ARGUMENT_MODEL    = 'model';
    public const ARGUMENT_BUILDER  = 'builder';
    public const ARGUMENT_RELATION = 'relation';
    public const ARGUMENT_RESOLVER = 'resolver';

    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    // <editor-fold desc="Directive">
    // =========================================================================
    public static function definition(): string {
        $model    = static::ARGUMENT_MODEL;
        $builder  = static::ARGUMENT_BUILDER;
        $relation = static::ARGUMENT_RELATION;
        $resolver = static::ARGUMENT_RESOLVER;

        return /** @lang GraphQL */ <<<GRAPHQL
            """
            Resolves the root for nested mutations.
            """
            directive @mutation(
                """
                Specify the class name of the model to use.
                This is only needed when the default model detection does not work.
                """
                {$model}: String

                """
                Specify the relation name to use.
                This replaces the use of a `{$model}`.
                """
                {$relation}: String

                """
                Point to a function that provides a Query Builder instance.
                This replaces the use of a `{$model}` and `{$relation}`.
                """
                {$builder}: String

                """
                Reference to a function that resolve the field.
                This replaces the use of a `{$model}`, `{$relation}` and `{$builder}`.
                Must return `Model` or `null`.
                """
                {$resolver}: String
            ) on FIELD_DEFINITION
            GRAPHQL;
    }
    // </editor-fold>

    // <editor-fold desc="FieldResolver">
    // =========================================================================
    public function resolveField(FieldValue $fieldValue): FieldValue {
        return $fieldValue->setResolver(
            function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Context {
                $root    = $root instanceof Context ? ($root->getModel() ?? $root->getParent()) : $root;
                $object  = $this->getObject($root, $args, $context, $resolveInfo);
                $context = new Context($root, $object);

                return $context;
            },
        );
    }
    // </editor-fold>

    // <editor-fold desc="FieldManipulator">
    // =========================================================================
    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        // Mutation cannot be mapped to model class due to the `Mutations`
        // suffix, so one of the arguments is always required.
        $requiredArguments = [
            static::ARGUMENT_MODEL,
            static::ARGUMENT_BUILDER,
            static::ARGUMENT_RELATION,
            static::ARGUMENT_RESOLVER,
        ];
        $arguments         = array_filter($requiredArguments, function (string $argument): bool {
            return $this->directiveHasArgument($argument);
        });

        if (!$arguments) {
            throw new DefinitionException(sprintf(
                'Directive `@%s` required at least one of `%s` argument.',
                $this->name(),
                implode('`, `', $requiredArguments),
            ));
        }

        // All nested fields must use one of mutations directives
        $manipulator             = $this->container->make(Manipulator::class, ['document' => $documentAST]);
        $requiredDirectives      = new Collection([
            Mutation::class,
            MutationCall::class,
            MutationCreate::class,
            MutationMutate::class,
        ]);
        $requiredDirectivesNames = $requiredDirectives
            ->map(static function (string $directive) use ($manipulator): string {
                return $manipulator->getDirectiveName($directive);
            });
        $fieldTypeDefinition     = $manipulator->getTypeDefinitionNode($fieldDefinition);

        if (!($fieldTypeDefinition instanceof ObjectTypeDefinitionNode)) {
            throw new DefinitionException(sprintf(
                'Field `%s.%s` must be a Type.',
                $manipulator->getNodeTypeName($parentType),
                $manipulator->getNodeName($fieldDefinition),
            ));
        }

        foreach ($fieldTypeDefinition->fields as $field) {
            $mutations = $requiredDirectives
                ->filter(static function (string $directive) use ($manipulator, $field): bool {
                    return (bool) $manipulator->getNodeDirective($field, $directive);
                });

            if ($mutations->isEmpty()) {
                throw new DefinitionException(sprintf(
                    'Field `%s.%s` must use one of `@%s` directives.',
                    $manipulator->getNodeTypeName($fieldTypeDefinition),
                    $manipulator->getNodeName($field),
                    $requiredDirectivesNames->join('`, `@'),
                ));
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="Resolve">
    // =========================================================================
    /**
     * @param array<mixed> $args
     */
    protected function getObject(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): ?Model {
        // Value
        $object   = null;
        $resolver = $this->getResolver(static::ARGUMENT_RESOLVER);

        if ($resolver) {
            $object = $resolver($root, $args, $context, $resolveInfo);

            if ($object !== null && !($object instanceof Model)) {
                throw new DefinitionException(sprintf(
                    'Resolver for directive `@%s` must return `null` or Model instance.',
                    $this->name(),
                ));
            }
        } elseif ($args) {
            $objects = $this->getBuilder($root, $args, $context, $resolveInfo)->limit(2)->get();
            $object  = $objects->first();

            if (count($objects) > 1) {
                throw new DefinitionException(sprintf(
                    'Builder for directive `@%s` must return only one Model.',
                    $this->name(),
                ));
            }

            if (!($object instanceof Model)) {
                throw new ObjectNotFound();
            }
        } else {
            // empty
        }

        return $object;
    }

    /**
     * @param array<mixed> $args
     */
    protected function getBuilder(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): EloquentBuilder|QueryBuilder|ScoutBuilder {
        // Get
        $builder  = null;
        $resolver = $this->getResolver(static::ARGUMENT_BUILDER);
        $relation = $this->directiveArgValue(static::ARGUMENT_RELATION);
        $model    = $this->directiveArgValue(static::ARGUMENT_MODEL);

        if ($resolver) {
            $builder = $resolver($root, $args, $context, $resolveInfo);
        } elseif (($root instanceof Model || $relation) && $model === null) {
            if (!($root instanceof Model)) {
                throw new DefinitionException(sprintf(
                    'Directive `@%s` argument `%s` can be used only when root is the Model.',
                    $this->name(),
                    static::ARGUMENT_RELATION,
                ));
            }

            $builder = (new ModelHelper($root))
                ->getRelation($this->getRelation(static::ARGUMENT_RELATION))
                ->getQuery();
        } else {
            $builder = $this->getModelClass(static::ARGUMENT_MODEL)::query();
        }

        // Builder?
        if (
            !($builder instanceof EloquentBuilder)
            && !($builder instanceof QueryBuilder)
            && !($builder instanceof ScoutBuilder)
        ) {
            throw new DefinitionException(sprintf(
                'Directive `@%s` must return an instance of Builder.',
                $this->name(),
            ));
        }

        // Enhance
        $builder = $resolveInfo->argumentSet->enhanceBuilder($builder, []);

        // Return
        return $builder;
    }

    protected function getResolver(string $argument): ?Closure {
        $value    = $this->directiveArgValue($argument);
        $resolver = null;

        if ($value !== null) {
            $resolver = $this->getResolverFromArgument($argument);
        }

        return $resolver;
    }

    protected function getRelation(string $argument): string {
        return $this->directiveArgValue($argument) ?? $this->nodeName();
    }
    // </editor-fold>
}
