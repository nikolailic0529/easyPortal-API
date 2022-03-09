<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\AstManipulator;
use Nuwave\Lighthouse\Support\Contracts\Directive;

class Manipulator extends AstManipulator {
    public function getNodeName(
        Type|FieldDefinition|InputValueDefinitionNode|InputObjectField|FieldDefinitionNode|TypeDefinitionNode $node,
    ): string {
        return parent::getNodeName($node);
    }

    public function getNodeTypeName(
        Node|Type|InputObjectField|FieldDefinition|TypeDefinitionNode|string $node,
    ): string {
        return parent::getNodeTypeName($node);
    }

    public function getTypeDefinitionNode(
        FieldDefinition|Node|InputObjectField|string $node,
    ): TypeDefinitionNode|Type {
        return parent::getTypeDefinitionNode($node);
    }

    public function getNodeDirective(Type|FieldDefinition|Node|InputObjectField $node, string $class): ?Directive {
        return parent::getNodeDirective($node, $class);
    }

    /**
     * @param class-string<\Nuwave\Lighthouse\Support\Contracts\Directive> $directive
     */
    public function getDirectiveName(string $directive): string {
        return $this->directives->directiveName($directive);
    }
}
