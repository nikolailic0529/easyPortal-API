<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\BuilderManipulator;
use App\GraphQL\Directives\Definitions\AggregatedGroupByDirective;
use App\GraphQL\Directives\Directives\Aggregated\Builder as AggregatedBuilder;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Group;
use App\GraphQL\Directives\Directives\Cached\Cached;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\ModelHelper;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;
use LogicException;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Scout\SearchDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;

use function implode;
use function is_a;
use function json_encode;
use function sprintf;
use function str_ends_with;

class Manipulator extends BuilderManipulator {
    public function __construct(
        DirectiveLocator $directives,
        DocumentAST $document,
        TypeRegistry $types,
        BuilderInfo $builderInfo,
        protected Repository $config,
        protected AggregatedGroupByDirective $groupByDirective,
    ) {
        parent::__construct($directives, $document, $types, $builderInfo);
    }

    // <editor-fold desc="Manipulate">
    // =========================================================================
    public function update(
        ObjectTypeDefinitionNode $parent,
        FieldDefinitionNode $field,
    ): void {
        // Add *Aggregate field
        $aggregated = $this->getAggregatedField($parent, $field);

        if ($aggregated) {
            $parent->fields[] = $this->applyManipulators($aggregated, $parent);
        }

        // Arguments
        $arguments = [
            $this->getLimitField(),
            $this->getOffsetField(),
            $this->getTrashedField($field),
        ];

        foreach ($arguments as $argument) {
            if ($argument) {
                $field->arguments[] = $argument;
            }
        }
    }

    protected function getAggregatedField(
        ObjectTypeDefinitionNode $parent,
        FieldDefinitionNode $field,
    ): ?FieldDefinitionNode {
        // Field exists?
        $fieldName = "{$this->getNodeName($field)}Aggregated";
        $existing  = $this->getObjectField($parent, $fieldName);

        if ($existing) {
            return null;
        }

        // Clone
        $aggregated = $this->clone($field);

        // Cleanup arguments
        foreach ($aggregated->arguments as $key => $argument) {
            $isArgBuilderDirective = (bool) $this->getNodeDirective($argument, ArgBuilderDirective::class);
            $isSearchDirective     = (bool) $this->getNodeDirective($argument, SearchDirective::class);
            $isSortByDirective     = (bool) $this->getNodeDirective($argument, SortByDirective::class);
            $isGroupByDirective    = (bool) $this->getNodeDirective($argument, AggregatedGroupByDirective::class);

            if ($isSortByDirective || (!$isArgBuilderDirective && !$isSearchDirective && !$isGroupByDirective)) {
                unset($aggregated->arguments[$key]);
            }
        }

        // Cleanup directives
        foreach ($aggregated->directives as $key => $directive) {
            $directive = $this->getDirectives()->create($directive->name->value);

            if ($directive instanceof Base || $directive instanceof RelationDirective) {
                unset($aggregated->directives[$key]);
            }

            if ($directive instanceof Cached) {
                unset($aggregated->directives[$key]);
            }
        }

        // Set type
        $typeName         = $this->getAggregatedFieldType($parent, $field);
        $aggregated->type = Parser::typeReference($typeName);

        // Set name
        $fieldName               = "{$this->getNodeName($field)}Aggregated";
        $aggregated->name->value = $fieldName;

        // Set description
        $aggregated->description = null;

        // Add @aggregated
        $arguments = $this->getNodeDirective($field, Base::class)?->getBuilderArguments() ?: [];
        $arguments = (new Collection($arguments))
            ->map(static function (mixed $value, string $key): string {
                return $key.': '.json_encode($value);
            })
            ->implode(',');
        $directive = $arguments
            ? Parser::directive("@aggregated({$arguments})")
            : Parser::directive('@aggregated');

        $aggregated->directives[] = $directive;

        // Trashed
        $trashed = $this->getTrashedField($field);

        if ($trashed) {
            $aggregated->arguments[] = $trashed;
        }

        // Return
        return $aggregated;
    }

    protected function getAggregatedFieldType(ObjectTypeDefinitionNode $parent, FieldDefinitionNode $node): string {
        // Prepare
        $isSearch    = (bool) (new Collection($node->arguments))
            ->first(function (InputValueDefinitionNode $arg): bool {
                return $this->getNodeDirective($arg, SearchDirective::class) !== null;
            });
        $nodeType    = $this->getNodeTypeName($node);
        $typeName    = Str::pluralStudly($nodeType).($isSearch ? 'Search' : '').'Aggregated';
        $parentType  = $this->getNodeTypeName($parent);
        $description = "Aggregated data for `{$this->getNodeTypeFullName($node)}`.";

        // Fields
        $isNested = str_ends_with($parentType, 'Aggregated');
        $fields   = [
            'count: Int! @aggregatedCount @cached(mode: Threshold)',
        ];

        if (!$isNested && !$isSearch) {
            $returnType   = $this->getType(Group::class, null, null);
            $sortByType   = (new Collection($node->arguments))
                ->first(function (InputValueDefinitionNode $arg): bool {
                    return $this->getNodeDirective($arg, SortByDirective::class) !== null;
                });
            $sortByType   = json_encode($sortByType ? $this->getNodeTypeName($sortByType) : null);
            $searchByType = (new Collection($node->arguments))
                ->first(function (InputValueDefinitionNode $arg): bool {
                    return $this->getNodeDirective($arg, SearchByDirective::class) !== null;
                });
            $searchByType = json_encode($searchByType ? $this->getNodeTypeName($searchByType) : null);
            $builder      = json_encode(AggregatedBuilder::class);
            $fields[]     = <<<DEF
                groups(
                    groupBy: {$nodeType}!
                    @aggregatedGroupBy(
                        where: {$searchByType}
                        order: {$sortByType}
                    )
                ): [{$returnType}!]!
                @cached(mode: Threshold)
                @paginated(builder: {$builder})
            DEF;
        }

        // Add type
        if ($this->isTypeDefinitionExists($typeName)) {
            // type?
            $definition = $this->getTypeDefinitionNode($typeName);

            if (!($definition instanceof ObjectTypeDefinitionNode)) {
                throw new LogicException(sprintf(
                    'Type `%s` already defined.',
                    $typeName,
                ));
            }

            // Description
            if (!$definition->description) {
                $definition->description = Parser::description("\"{$description}\"");
            }

            // Fields
            foreach ($fields as $fieldDefinition) {
                // Exists?
                $fieldNode = Parser::fieldDefinition($fieldDefinition);
                $fieldName = $this->getNodeName($fieldNode);
                $existing  = $this->getObjectField($definition, $fieldName);

                if ($existing === null) {
                    $definition->fields[] = $this->applyManipulators($fieldNode, $definition);
                }
            }
        } else {
            $fieldsDefinition = implode("\n", $fields);
            $typeDefinition   = $this->addTypeDefinition(Parser::objectTypeDefinition(
                <<<DEF
                """
                {$description}
                """
                type {$typeName} {
                    {$fieldsDefinition}
                }
                DEF,
            ));

            $this->applyManipulators($typeDefinition);
        }

        // Return
        return $typeName;
    }
    // </editor-fold>

    // <editor-fold desc="Fields">
    // =========================================================================
    protected function getOffsetField(): InputValueDefinitionNode {
        return Parser::inputValueDefinition(
            <<<'DEF'
            offset: Int! = 0 @rules(apply: ["min:0"]) @paginatedOffset
            DEF,
        );
    }

    protected function getLimitField(): InputValueDefinitionNode {
        $default = (int) $this->config->get('ep.pagination.limit.default');
        $value   = $default > 0 ? "= {$default}" : '';
        $rules   = json_encode([
            'min:1',
            LimitRule::class,
        ]);

        return Parser::inputValueDefinition(
            <<<DEF
            limit: Int! {$value} @rules(apply: {$rules}) @paginatedLimit
            DEF,
        );
    }

    protected function getTrashedField(FieldDefinitionNode $field): ?InputValueDefinitionNode {
        // SoftDelete?
        $type      = $this->getNodeTypeName($field);
        $model     = Relation::getMorphedModel($type);
        $deletable = $model
            && is_a($model, Model::class, true)
            && (new ModelHelper($model))->isSoftDeletable();

        if (!$deletable) {
            return null;
        }

        // Scout?
        $scout = Arr::first($field->arguments, function (InputValueDefinitionNode $arg): bool {
            return $this->getNodeDirective($arg, SearchDirective::class) !== null;
        });

        if ($scout !== null) {
            return null;
        }

        // Return
        return Parser::inputValueDefinition(
            <<<'DEF'
            trashed: Trashed
            @authMe(permissions: ["administer"])
            @paginatedTrashed
            DEF,
        );
    }
    // </editor-fold>

    // <editor-fold desc="Names">
    // =========================================================================
    protected function getTypeName(string $name, string $scalar = null, bool $nullable = null): string {
        return Paginated::NAME.'Type'.Str::studly($name);
    }
    // </editor-fold>
}
