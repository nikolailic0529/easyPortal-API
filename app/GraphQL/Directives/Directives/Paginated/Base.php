<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\BuilderArguments;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Container\Container;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;

abstract class Base extends BaseDirective implements FieldManipulator {
    use BuilderArguments;

    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        $this->container
            ->make(Manipulator::class, ['document' => $documentAST])
            ->update($parentType, $fieldDefinition);
    }
}
