<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\Services\Organization\CurrentOrganization;
use App\Utils\Eloquent\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

abstract class Relation extends BaseDirective implements FieldResolver {
    use RelationDirectiveHelpers;

    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Return relation value for current organization.
            """
            directive @orgRelation on FIELD_DEFINITION
        GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        $fieldValue->setResolver(
            function (mixed $parent, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) {
                assert($parent instanceof Model);

                $name     = $resolveInfo->fieldName;
                $relation = (new ModelHelper($parent))->getRelation($resolveInfo->fieldName);
                $property = null;

                if ($relation instanceof BelongsTo) {
                    $property = $relation->getForeignKeyName();
                }

                if (!$property) {
                    return null;
                }

                /** @var RelationBatchLoader $batchLoader */
                $batchLoader = BatchLoaderRegistry::instance(
                    array_merge(
                        $this->qualifyPath($args, $resolveInfo),
                        ["@{$this->name()}"],
                    ),
                    function () use ($property, $name): RelationBatchLoader {
                        return new RelationBatchLoader(
                            new RelationLoader($this->organization, $name, $property),
                        );
                    },
                );

                return $batchLoader->load($parent);
            },
        );

        return $fieldValue;
    }
}
