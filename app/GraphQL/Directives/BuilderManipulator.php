<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use Nuwave\Lighthouse\Support\Contracts\ArgManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;

use function assert;
use function get_object_vars;
use function is_array;

class BuilderManipulator extends Manipulator {
    /**
     * @template T of Node
     *
     * @param T $field
     *
     * @return T
     */
    public function clone(Node $field): Node {
        // Seems will be solved in webonyx/graphql-php v15?
        //
        // https://github.com/webonyx/graphql-php/issues/988
        return (new class([]) extends Node {
            /**
             * @template T of Node
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

    /**
     * @return ($object is InputObjectTypeDefinitionNode
     *      ? InputValueDefinitionNode|null
     *      : ($object is ObjectTypeDefinitionNode
     *          ? FieldDefinitionNode|null
     *          : ($object is InputObjectType
     *              ? InputObjectField|null
     *              : FieldDefinition|null
     *      )))
     */
    public function getObjectField(
        InputObjectTypeDefinitionNode|ObjectTypeDefinitionNode|InputObjectType|ObjectType $object,
        string $name,
    ): InputValueDefinitionNode|FieldDefinitionNode|InputObjectField|FieldDefinition|null {
        $fields = $object instanceof InputObjectType || $object instanceof ObjectType
            ? $object->getFields()
            : $object->fields;
        $field  = (new Collection($fields))
            ->first(function (
                InputValueDefinitionNode|InputObjectField|FieldDefinitionNode|FieldDefinition $field,
            ) use (
                $name,
            ): bool {
                return $this->getNodeName($field) === $name;
            });

        return $field;
    }

    /**
     * @template T of ObjectTypeDefinitionNode|FieldDefinitionNode|InputValueDefinitionNode
     *
     * @param T $node
     *
     * @return T
     */
    public function applyManipulators(
        ObjectTypeDefinitionNode|FieldDefinitionNode|InputValueDefinitionNode $node,
        ObjectTypeDefinitionNode $parentObject = null,
        FieldDefinitionNode $parentField = null,
    ): ObjectTypeDefinitionNode|FieldDefinitionNode|InputValueDefinitionNode {
        // There is no way to guarantee that manipulators will be applied for
        // newly added types and fields :(
        $document   = $this->getDocument();
        $directives = $this->getDirectives();

        if ($node instanceof ObjectTypeDefinitionNode) {
            /** @see ASTBuilder::applyTypeDefinitionManipulators() */
            $manipulators = $directives->associatedOfType($node, TypeManipulator::class);

            foreach ($manipulators as $manipulator) {
                $manipulator->manipulateTypeDefinition($document, $node);
            }

            foreach ($node->fields as $field) {
                assert($field instanceof FieldDefinitionNode);

                $this->applyManipulators($field, $node);
            }
        } elseif ($node instanceof FieldDefinitionNode) {
            /** @see ASTBuilder::applyFieldManipulators() */
            $manipulators = $directives->associatedOfType($node, FieldManipulator::class);

            foreach ($manipulators as $manipulator) {
                $manipulator->manipulateFieldDefinition($document, $node, $parentObject);
            }

            foreach ($node->arguments as $argument) {
                assert($argument instanceof InputValueDefinitionNode);

                $this->applyManipulators($argument, $parentObject, $node);
            }
        } else {
            /** @see ASTBuilder::applyArgManipulators() */
            $manipulators = $directives->associatedOfType($node, ArgManipulator::class);

            foreach ($manipulators as $manipulator) {
                $manipulator->manipulateArgDefinition($document, $node, $parentField, $parentObject);
            }
        }

        return $node;
    }
}
