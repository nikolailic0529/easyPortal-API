<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\HandlerDirective;
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
        $this->getContainer()
            ->make(Manipulator::class, ['document' => $documentAST])
            ->update($this, $parentType, $parentField, $argDefinition);
    }

    /**
     * @inheritDoc
     * @return EloquentBuilder<Model>|QueryBuilder
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        return $this->handleAnyBuilder($builder, $value);
    }

    public function getTypeProvider(DocumentAST $document): TypeProvider {
        return $this->getContainer()->make(Manipulator::class, [
            'document' => $document,
        ]);
    }

    public function getArgumentValue(string $name, mixed $default = null): mixed {
        return parent::directiveArgValue($name, $default);
    }
}
