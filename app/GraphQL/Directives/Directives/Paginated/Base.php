<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\BuilderArguments;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

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

    public function resolveField(FieldValue $fieldValue): FieldValue {
        return $fieldValue->setResolver(
            function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection {
                return $this->getBuilder($root, $args, $context, $resolveInfo)->get();
            },
        );
    }
}
