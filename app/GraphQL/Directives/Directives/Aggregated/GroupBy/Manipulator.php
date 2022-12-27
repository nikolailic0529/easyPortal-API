<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy;

use App\GraphQL\Directives\BuilderManipulator;
use App\GraphQL\Directives\Directives\Aggregated\Aggregated;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Exceptions\FailedToCreateGroupClause;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\AsDate;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\AsString;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\Relation;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Group;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

use function count;
use function is_string;
use function str_starts_with;

/**
 * @deprecated Would be good to convert into Operator/Type
 */
class Manipulator extends BuilderManipulator {
    public function __construct(
        DirectiveLocator $directives,
        DocumentAST $document,
        TypeRegistry $types,
        BuilderInfo $builderInfo,
        private Relation $relationDirective,
        private AsDate $asDateDirective,
        private AsString $asStringDirective,
    ) {
        parent::__construct($directives, $document, $types, $builderInfo);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function getRelationDirective(): Relation {
        return $this->relationDirective;
    }

    protected function getAsDateDirective(): AsDate {
        return $this->asDateDirective;
    }

    protected function getAsStringDirective(): AsString {
        return $this->asStringDirective;
    }
    // </editor-fold>

    // <editor-fold desc="API">
    // =========================================================================
    public function update(
        Directive $directive,
        ObjectTypeDefinitionNode $parent,
        FieldDefinitionNode $field,
        InputValueDefinitionNode $node,
    ): void {
        // Convert
        $type = null;

        if (!$this->isTypeName($node)) {
            $definition  = $this->getTypeDefinitionNode($node);
            $isSupported = $definition instanceof InputObjectTypeDefinitionNode
                || $definition instanceof ObjectTypeDefinitionNode
                || $definition instanceof InputObjectType
                || $definition instanceof ObjectType;

            if ($isSupported) {
                $name = $this->getInputType($directive, $parent, $field, $definition);

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
            $group       = $this->getType(Group::class, null, null);
            $field->type = Parser::typeReference("[{$group}!]!");
        }
    }
    // </editor-fold>

    // <editor-fold desc="Types">
    // =========================================================================
    protected function getInputType(
        Directive $directive,
        ObjectTypeDefinitionNode $parentObject,
        FieldDefinitionNode $parentField,
        InputObjectTypeDefinitionNode|ObjectTypeDefinitionNode|InputObjectType|ObjectType $node,
    ): ?string {
        // Types
        // (temporary solution until we don't use `_` for all)
        $whereTypeName = $directive->getArgumentValue('where');
        $whereTypeNode = is_string($whereTypeName) && $this->isTypeDefinitionExists($whereTypeName)
            ? $this->getTypeDefinitionNode($whereTypeName)
            : null;
        $whereTypeNode = $whereTypeNode instanceof InputObjectTypeDefinitionNode
            ? $whereTypeNode
            : null;

        if ($whereTypeNode) {
            $node = $whereTypeNode;
        }

        $orderTypeName = $directive->getArgumentValue('order');
        $orderTypeNode = is_string($orderTypeName) && $this->isTypeDefinitionExists($orderTypeName)
            ? $this->getTypeDefinitionNode($orderTypeName)
            : null;
        $orderTypeNode = $orderTypeNode instanceof InputObjectTypeDefinitionNode
            ? $orderTypeNode
            : $node;

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
        $relation  = $this->getRelationDirective();
        $fields    = $node instanceof InputObjectType || $node instanceof ObjectType
            ? $node->getFields()
            : $node->fields;
        $supported = [
            Type::ID     => $this->getAsStringDirective(),
            Type::INT    => $this->getAsStringDirective(),
            Type::STRING => $this->getAsStringDirective(),
            'Date'       => $this->getAsDateDirective(),
        ];

        foreach ($fields as $field) {
            // Convertable?
            if ($this->isList($field) || $this->getNodeDirective($field, FieldResolver::class) !== null) {
                continue;
            }

            // PK?
            if ($this->getNodeName($field) === 'id') {
                continue;
            }

            // Nested?
            $fieldTypeNode = $this->getTypeDefinitionNode($field);
            $fieldType     = $this->getNodeTypeName($field);
            $fieldName     = $this->getNodeName($field);
            $isNested      = $fieldTypeNode instanceof InputObjectTypeDefinitionNode
                || $fieldTypeNode instanceof ObjectTypeDefinitionNode
                || $fieldTypeNode instanceof InputObjectType
                || $fieldTypeNode instanceof ObjectType;

            if ($isNested) {
                // Sorting possible only by the field used for aggregation so we
                // check that related field is exists (it is not the best way,
                // but it is work) and has ID type.
                $idName     = Str::snake("{$fieldName}_id");
                $idField    = $this->getObjectField($node, $idName);
                $orderField = $node !== $orderTypeNode
                    ? $this->getObjectField($orderTypeNode, $fieldName)
                    : $field;

                if ($orderField === null || $idField === null || $this->getNodeTypeName($idField) !== Type::ID) {
                    continue;
                }

                // Add @sortBy (is there a better way to get proper type?)
                $orderField       = $this->applyManipulators(
                    Parser::inputValueDefinition(
                        <<<GRAPHQL
                        {$fieldName}: {$this->getNodeTypeName($orderField)}
                        @sortBy
                        GRAPHQL,
                    ),
                    $parentObject,
                    $parentField,
                );
                $fieldType        = Printer::doPrint($orderField->type);
                $fieldDirective   = $relation::getDirectiveName();
                $fieldDescription = "Group by `{$idName}` with additional sorting.";
                $type->fields[]   = Parser::inputValueDefinition(
                    <<<GRAPHQL
                        "{$fieldDescription}"
                        {$fieldName}: {$fieldType}
                        {$fieldDirective}
                        GRAPHQL,
                );
            } else {
                // Supported?
                $fieldOperator = $supported[$fieldType] ?? null;

                if (!$fieldOperator) {
                    continue;
                }

                // Create new Field
                $fieldType      = $this->getTypeDefinitionNode($fieldType);
                $type->fields[] = Parser::inputValueDefinition(
                    $this->getOperatorField(
                        $fieldOperator,
                        $fieldType,
                        $fieldName,
                        null,
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
