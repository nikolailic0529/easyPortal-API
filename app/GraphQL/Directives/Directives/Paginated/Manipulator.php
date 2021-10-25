<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\GraphQL\AstManipulator;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\TypeRegistry;

use function json_encode;

class Manipulator extends AstManipulator {
    public function __construct(
        DirectiveLocator $directives,
        DocumentAST $document,
        TypeRegistry $types,
        protected Repository $config,
    ) {
        parent::__construct($directives, $document, $types);
    }

    public function update(
        FieldDefinitionNode $fieldDefinition,
        ObjectTypeDefinitionNode $parentType,
    ): void {
        $fieldDefinition->arguments[] = $this->getLimitField();
        $fieldDefinition->arguments[] = $this->getOffsetField();
    }

    protected function getOffsetField(): InputValueDefinitionNode {
        return Parser::inputValueDefinition(
            <<<'DEF'
            offset: Int! = 0 @rules(apply: ["min:0"])
            DEF,
        );
    }

    protected function getLimitField(): InputValueDefinitionNode {
        $min     = 1;
        $max     = (int) $this->config->get('ep.pagination.limit.max');
        $max     = $max > 0 ? $max : 1000;
        $default = (int) $this->config->get('ep.pagination.limit.default');
        $value   = $default > 0 ? "= {$default}" : '';
        $rules   = json_encode([
            "min:{$min}",
            "min:{$max}",
        ]);

        return Parser::inputValueDefinition(
            <<<DEF
            "Maximum value is {$max}."
            limit: Int! {$value} @rules(apply: {$rules})
            DEF,
        );
    }
}
