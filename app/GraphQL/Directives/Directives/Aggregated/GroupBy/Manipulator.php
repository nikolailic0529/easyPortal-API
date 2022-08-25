<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy;

use App\GraphQL\Directives\Directives\Aggregated\Aggregated;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Exceptions\FailedToCreateGroupClause;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\Property;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\PropertyOperator;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Direction;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Group;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator as BuilderManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

use function count;
use function str_starts_with;

class Manipulator extends BuilderManipulator {
    // <editor-fold desc="API">
    // =========================================================================
    public function update(FieldDefinitionNode $field, InputValueDefinitionNode $node): void {
        // Convert
        $type = null;

        if (!$this->isTypeName($node)) {
            $definition  = $this->getTypeDefinitionNode($node);
            $isSupported = $definition instanceof InputObjectTypeDefinitionNode
                || $definition instanceof ObjectTypeDefinitionNode
                || $definition instanceof InputObjectType
                || $definition instanceof ObjectType;

            if ($isSupported) {
                $name = $this->getInputType($definition);

                if ($name) {
                    $type = Parser::typeReference("{$name}!");
                }
            }
        } else {
            $type = $node->type;
        }

        // Success?
        if (!$type) {
            throw new FailedToCreateGroupClause(
                $this->getNodeTypeFullName($node),
            );
        }

        // Update
        $node->type = $type;

        if ($this->getNodeDirective($field, Aggregated::class) === null) {
            $group       = $this->getType(Group::class);
            $field->type = Parser::typeReference("[{$group}!]!");
        }
    }
    // </editor-fold>

    // <editor-fold desc="Types">
    // =========================================================================
    protected function getInputType(
        InputObjectTypeDefinitionNode|ObjectTypeDefinitionNode|InputObjectType|ObjectType $node,
    ): ?string {
        // Exists?
        $name = $this->getInputTypeName($node);

        if ($this->isTypeDefinitionExists($name)) {
            return $name;
        }

        // Add type
        $type = $this->addTypeDefinition(
            Parser::inputObjectTypeDefinition(
                <<<DEF
                """
                Group clause for `{$this->getNodeTypeFullName($node)}` (only one property allowed at a time).
                """
                input {$name} {
                    """
                    If you see this probably something wrong. Please contact to developer.
                    """
                    dummy: ID
                }
                DEF,
            ),
        );

        // Add sortable fields
        $direction = $this->getType(Direction::class);
        $operator  = $this->getContainer()->make(PropertyOperator::class);
        $property  = $this->getContainer()->make(Property::class);
        $fields    = $node instanceof InputObjectType || $node instanceof ObjectType
            ? $node->getFields()
            : $node->fields;
        $supported = [
            Type::ID     => true,
            Type::STRING => true,
        ];

        foreach ($fields as $field) {
            // Convertable?
            if ($this->isList($field) || $this->getNodeDirective($field, FieldResolver::class) !== null) {
                continue;
            }

            // Nested?
            $fieldType     = $direction;
            $fieldOperator = $operator;
            $fieldTypeNode = $this->getTypeDefinitionNode($field);
            $isSupported   = isset($supported[$this->getNodeTypeName($field)]);
            $isNested      = $fieldTypeNode instanceof InputObjectTypeDefinitionNode
                || $fieldTypeNode instanceof ObjectTypeDefinitionNode
                || $fieldTypeNode instanceof InputObjectType
                || $fieldTypeNode instanceof ObjectType;

            if ($isNested) {
                // Not supported yet
                //
                // $fieldType     = $this->getInputType($fieldTypeNode);
                // $fieldOperator = $property;
                continue;
            } elseif (!$isSupported) {
                continue;
            } else {
                // empty
            }

            // Create new Field
            if ($fieldType) {
                $type->fields[] = Parser::inputValueDefinition(
                    $this->getOperatorField(
                        $fieldOperator,
                        $this->getTypeDefinitionNode($fieldType),
                        $this->getNodeName($field),
                    ),
                );
            }
        }

        // Remove dummy
        unset($type->fields[0]);

        // Empty?
        if (count($type->fields) === 0) {
            $this->removeTypeDefinition($name);

            $name = null;
        }

        // Return
        return $name;
    }
    // </editor-fold>

    // <editor-fold desc="Names">
    // =========================================================================
    protected function isTypeName(
        Node|Type|InputObjectField|FieldDefinition|string $node,
    ): bool {
        return str_starts_with($this->getNodeTypeName($node), Directive::NAME);
    }

    protected function getTypeName(string $name, string $scalar = null, bool $nullable = null): string {
        return Directive::NAME.'Type'.Str::studly($name);
    }

    protected function getInputTypeName(
        InputObjectTypeDefinitionNode|ObjectTypeDefinitionNode|InputObjectType|ObjectType $node,
    ): string {
        return Directive::NAME."Clause{$this->getNodeName($node)}";
    }
    // </editor-fold>
}
