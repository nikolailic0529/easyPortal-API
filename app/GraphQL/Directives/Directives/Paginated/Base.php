<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\BuilderArguments;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;

abstract class Base extends BaseDirective implements FieldManipulator, FieldBuilderDirective {
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

    public function handleFieldBuilder(object $builder): object {
        if ($builder instanceof Builder) {
            $key = $builder->getModel()->getKeyName();

            if ($key) {
                $builder->orderBy($key);
            }
        }

        return $builder;
    }
}
