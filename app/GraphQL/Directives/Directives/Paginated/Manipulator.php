<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\Definitions\AggregatedGroupByDirective;
use App\GraphQL\Directives\Directives\Aggregated\Builder as AggregatedBuilder;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Group;
use App\GraphQL\Directives\Directives\Cached\Cached;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;
use LastDragon_ru\LaraASP\GraphQL\Utils\AstManipulator;
use LogicException;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Scout\SearchDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;

use function assert;
use function get_object_vars;
use function implode;
use function is_array;
use function json_encode;
use function sprintf;
use function str_ends_with;

class Manipulator extends AstManipulator {
    public function __construct(
        DirectiveLocator $directives,
        DocumentAST $document,
        TypeRegistry $types,
        protected Repository $config,
        protected AggregatedGroupByDirective $groupByDirective,
    ) {
        parent::__construct($directives, $document, $types);
    }

    public function update(
        ObjectTypeDefinitionNode $parent,
        FieldDefinitionNode $field,
    ): void {
        // Add *Aggregate field
        $aggregated = $this->getAggregatedField($parent, $field);

        if ($aggregated) {
            $parent->fields[] = $this->applyManipulators($aggregated, $parent);
        }

        // Add limit/offset arguments
        $field->arguments[] = $this->getLimitField();
        $field->arguments[] = $this->getOffsetField();
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
            $directive = $this->directives->create($directive->name->value);

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
            $returnType   = $this->groupByDirective->getTypeProvider($this->document)->getType(Group::class);
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

    /**
     * @template T of \GraphQL\Language\AST\Node
     *
     * @param T $field
     *
     * @return T
     */
    private function clone(Node $field): Node {
        // Seems will be solved in webonyx/graphql-php v15?
        //
        // https://github.com/webonyx/graphql-php/issues/988
        return (new class([]) extends Node {
            /**
             * @template T of \GraphQL\Language\AST\Node
             *
             * @param T $node
             *
             * @return T
             */
            public function clone(Node $node): Node {
                return $this->cloneValue($node);
            }

            /**
             * @template T
             *
             * @param T $value
             *
             * @return T
             */
            private function cloneValue(mixed $value): mixed {
                if (is_array($value)) {
                    $cloned = [];

                    foreach ($value as $key => $arrValue) {
                        $cloned[$key] = $this->cloneValue($arrValue);
                    }
                } elseif ($value instanceof Node) {
                    $cloned = clone $value;

                    foreach (get_object_vars($cloned) as $prop => $propValue) {
                        $cloned->{$prop} = $this->cloneValue($propValue);
                    }
                } elseif ($value instanceof NodeList) {
                    $cloned = clone $value;

                    foreach ($value as $key => $listValue) {
                        $cloned[$key] = $this->cloneValue($listValue);
                    }
                } else {
                    $cloned = $value;
                }

                return $cloned;
            }
        })->clone($field);
    }

    protected function getObjectField(ObjectTypeDefinitionNode $object, string $name): ?FieldDefinitionNode {
        return (new Collection($object->fields))
            ->first(function (FieldDefinitionNode $field) use ($name): bool {
                return $this->getNodeName($field) === $name;
            });
    }

    /**
     * @template T of ObjectTypeDefinitionNode|FieldDefinitionNode|InputValueDefinitionNode
     *
     * @param T $node
     *
     * @return T
     */
    protected function applyManipulators(
        ObjectTypeDefinitionNode|FieldDefinitionNode|InputValueDefinitionNode $node,
        ObjectTypeDefinitionNode $parentObject = null,
        FieldDefinitionNode $parentField = null,
    ): ObjectTypeDefinitionNode|FieldDefinitionNode|InputValueDefinitionNode {
        // There is no way to guarantee that manipulators will be applied for
        // newly added types and fields :(

        if ($node instanceof ObjectTypeDefinitionNode) {
            /** @see ASTBuilder::applyTypeDefinitionManipulators() */
            $manipulators = $this->directives->associatedOfType($node, TypeManipulator::class);

            foreach ($manipulators as $manipulator) {
                $manipulator->manipulateTypeDefinition($this->document, $node);
            }

            foreach ($node->fields as $field) {
                assert($field instanceof FieldDefinitionNode);

                $this->applyManipulators($field, $node);
            }
        } elseif ($node instanceof FieldDefinitionNode) {
            /** @see ASTBuilder::applyFieldManipulators() */
            $manipulators = $this->directives->associatedOfType($node, FieldManipulator::class);

            foreach ($manipulators as $manipulator) {
                $manipulator->manipulateFieldDefinition($this->document, $node, $parentObject);
            }

            foreach ($node->arguments as $argument) {
                assert($argument instanceof InputValueDefinitionNode);

                $this->applyManipulators($argument, $parentObject, $node);
            }
        } else {
            /** @see ASTBuilder::applyArgManipulators() */
            $manipulators = $this->directives->associatedOfType($node, ArgManipulator::class);

            foreach ($manipulators as $manipulator) {
                $manipulator->manipulateArgDefinition($this->document, $node, $parentField, $parentObject);
            }
        }

        return $node;
    }
}
