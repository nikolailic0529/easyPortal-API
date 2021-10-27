<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\Directives\Aggregated\AggregatedCount;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\AstManipulator;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;
use LogicException;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Scout\SearchDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;

use function get_object_vars;
use function is_array;
use function json_encode;
use function sprintf;

class Manipulator extends AstManipulator {
    public function __construct(
        DirectiveLocator $directives,
        DocumentAST $document,
        TypeRegistry $types,
        protected Repository $config,
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
            $parent->fields[] = $aggregated;
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
        $existing  = Arr::first($parent->fields, function (FieldDefinitionNode $field) use ($fieldName): bool {
            return $this->getNodeName($field) === $fieldName;
        });

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

            if ($isSortByDirective || (!$isArgBuilderDirective && !$isSearchDirective)) {
                unset($aggregated->arguments[$key]);
            }
        }

        // Cleanup directives
        foreach ($aggregated->directives as $key => $directive) {
            $directive = $this->directives->create($directive->name->value);

            if ($directive instanceof Base || $directive instanceof RelationDirective) {
                unset($aggregated->directives[$key]);
            }
        }

        // Set type
        $typeName         = $this->getAggregatedFieldType($field);
        $aggregated->type = Parser::typeReference($typeName);

        // Set name
        $fieldName               = "{$this->getNodeName($field)}Aggregated";
        $aggregated->name->value = $fieldName;

        // Set description
        // TODO $aggregated->description = null;

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

    protected function getAggregatedFieldType(FieldDefinitionNode $node): string {
        $typeName    = Str::pluralStudly($this->getNodeTypeName($node)).'Aggregated';
        $fieldName   = 'count';
        $fieldSource = "{$fieldName}: Int! @aggregatedCount";

        if ($this->isTypeDefinitionExists($typeName)) {
            // type?
            $definition = $this->getTypeDefinitionNode($typeName);

            if (!($definition instanceof ObjectTypeDefinitionNode)) {
                throw new LogicException(sprintf(
                    'Type `%s` already defined.',
                    $typeName,
                ));
            }

            // count?
            $existing = Arr::first($definition->fields, function (FieldDefinitionNode $field) use ($fieldName): bool {
                return $this->getNodeName($field) === $fieldName;
            });

            if ($existing && !$this->getNodeDirective($existing, AggregatedCount::class)) {
                throw new LogicException(sprintf(
                    'Field `%s` in type `%s` already defined.',
                    $fieldName,
                    $typeName,
                ));
            }

            // Add
            $definition->fields[] = Parser::fieldDefinition($fieldSource);
        } else {
            $this->addTypeDefinition(Parser::objectTypeDefinition(
                <<<DEF
                """
                Aggregated data for {$this->getNodeTypeFullName($node)}.
                """
                type {$typeName} {
                    {$fieldSource}
                }
                DEF,
            ));
        }

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
        $min     = 1;
        $max     = (int) $this->config->get('ep.pagination.limit.max');
        $max     = $max > 0 ? $max : 1000;
        $default = (int) $this->config->get('ep.pagination.limit.default');
        $value   = $default > 0 ? "= {$default}" : '';
        $rules   = json_encode([
            "min:{$min}",
            "max:{$max}",
        ]);

        return Parser::inputValueDefinition(
            <<<DEF
            "Maximum value is {$max}."
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
}
