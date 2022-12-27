<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy;

use Exception;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\HandlerDirective;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator as BaseManipulator;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgManipulator;

abstract class Directive extends HandlerDirective implements ArgManipulator, ArgBuilderDirective {
    public const NAME = 'AggregatedGroupBy';

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            directive @aggregatedGroupBy(
                where: String
                order: String
            ) on ARGUMENT_DEFINITION
            GRAPHQL;
    }

    public function manipulateArgDefinition(
        DocumentAST &$documentAST,
        InputValueDefinitionNode &$argDefinition,
        FieldDefinitionNode &$parentField,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        Container::getInstance()
            ->make(Manipulator::class, [
                'document'    => $documentAST,
                'builderInfo' => $this->getBuilderInfo($parentField),
            ])
            ->update($this, $parentType, $parentField, $argDefinition);
    }

    protected function isTypeName(string $name): bool {
        throw new Exception('Should not be used.');
    }

    protected function getArgDefinitionType(
        BaseManipulator $manipulator,
        DocumentAST $document,
        InputValueDefinitionNode $argument,
        FieldDefinitionNode $field,
    ): ListTypeNode|NamedTypeNode|NonNullTypeNode {
        throw new Exception('Should not be used.');
    }

    public function getArgumentValue(string $name, mixed $default = null): mixed {
        return parent::directiveArgValue($name, $default);
    }
}
