<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\BuilderArguments;
use App\Utils\Eloquent\Callbacks\OrderByKey;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use stdClass;

abstract class Base extends BaseDirective implements FieldManipulator, FieldBuilderDirective {
    use BuilderArguments;

    public function __construct(
        protected Container $container,
        protected OrderByKey $orderByCallback,
    ) {
        // empty
    }

    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        $this->container
            ->make(Manipulator::class, [
                'document'    => $documentAST,
                'builderInfo' => new BuilderInfo('Any', new stdClass()),
            ])
            ->update($parentType, $fieldDefinition);
    }

    public function handleFieldBuilder(object $builder): object {
        if ($builder instanceof Builder) {
            $builder = ($this->orderByCallback)($builder);
        }

        return $builder;
    }
}
