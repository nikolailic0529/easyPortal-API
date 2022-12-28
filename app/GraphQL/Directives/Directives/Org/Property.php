<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\GraphQL\Extensions\LaraAsp\Builder\Contracts\Extender;
use App\Services\Organization\CurrentOrganization;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property as BuilderProperty;
use Nuwave\Lighthouse\Execution\BatchLoader\BatchLoaderRegistry;
use Nuwave\Lighthouse\Execution\BatchLoader\RelationBatchLoader;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Directives\RelationDirectiveHelpers;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_merge;
use function assert;
use function sprintf;

// TODO Update property description?

abstract class Property extends BaseDirective implements FieldResolver, Extender {
    use RelationDirectiveHelpers;

    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Return property value for current organization.
            """
            directive @orgProperty on FIELD_DEFINITION | INPUT_FIELD_DEFINITION
        GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        $fieldValue->setResolver(
            function (mixed $parent, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) {
                assert($parent instanceof Model);

                /** @var RelationBatchLoader $batchLoader */
                $batchLoader = BatchLoaderRegistry::instance(
                    array_merge(
                        $this->qualifyPath($args, $resolveInfo),
                        ["@{$this->name()}"],
                    ),
                    function () use ($resolveInfo): RelationBatchLoader {
                        return new RelationBatchLoader(
                            new Loader(
                                $this->organization,
                                $resolveInfo->fieldName,
                                $this->getDefaultValue($resolveInfo->returnType),
                            ),
                        );
                    },
                );

                return $batchLoader->load($parent);
            },
        );

        return $fieldValue;
    }

    protected function getDefaultValue(Type $type): mixed {
        $value = null;

        if ($type instanceof NonNull) {
            $type = $type->getOfType();

            if ($type === Type::int() || $type === Type::float()) {
                $value = 0;
            }
        }

        return $value;
    }

    // <editor-fold desc="Extender">
    // =========================================================================
    public function getFieldDirective(): ?DirectiveNode {
        return $this->directiveNode;
    }

    public function isBuilderSupported(object $builder): bool {
        return $builder instanceof EloquentBuilder;
    }

    public function extend(object $builder, BuilderProperty $property): object {
        // Supported?
        if (!($builder instanceof EloquentBuilder)) {
            throw new InvalidArgumentException(sprintf(
                'Builder must be instance of `%s`, `%s` given.',
                EloquentBuilder::class,
                $builder::class,
            ));
        }

        // Add property
        $name  = $property->getParent()->getName();
        $query = (new Loader($this->organization, $name))->getQuery($builder);

        if ($query) {
            $builder->selectSub($query->limit(1), $name);
        }

        // Return
        return $builder;
    }
    // </editor-fold>
}
