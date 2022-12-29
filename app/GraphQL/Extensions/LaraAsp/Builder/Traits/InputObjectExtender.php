<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\Builder\Traits;

use App\GraphQL\Extensions\LaraAsp\Builder\Contracts\Extender;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Types\InputObject;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\Ignored;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

/**
 * @mixin InputObject
 */
trait InputObjectExtender {
    protected function isConvertable(
        Manipulator $manipulator,
        Type|FieldDefinition|InputValueDefinitionNode|FieldDefinitionNode|InputObjectField|TypeDefinitionNode $node,
    ): bool {
        // Union?
        if ($manipulator->isUnion($node)) {
            return false;
        }

        // Resolver?
        $resolver = $manipulator->getNodeDirective($node, FieldResolver::class);
        $extender = $manipulator->getNodeDirective($node, Extender::class);

        if ($resolver !== null && $extender === null) {
            return false;
        }

        // Ignored?
        if ($node instanceof Ignored || $manipulator->getNodeDirective($node, Ignored::class) !== null) {
            return false;
        }

        // Ok
        return true;
    }

    protected function getFieldDefinition(
        Manipulator $manipulator,
        FieldDefinition|InputValueDefinitionNode|FieldDefinitionNode|InputObjectField $field,
        Type|TypeDefinitionNode $fieldType,
        ?bool $fieldNullable,
    ): ?InputValueDefinitionNode {
        $definition = parent::getFieldDefinition($manipulator, $field, $fieldType, $fieldNullable);

        if ($definition) {
            $directives = $manipulator
                ->getNodeDirectives($field, Extender::class)
                ->map(static function (Extender $extender): ?string {
                    $directive = $extender->getFieldDirective();
                    $directive = $directive
                        ? Printer::doPrint($directive)
                        : null;

                    return $directive;
                })
                ->filter()
                ->implode(' ');
            $definition = $directives
                ? Parser::inputValueDefinition(Printer::doPrint($definition).' '.$directives)
                : $definition;
        }

        return $definition;
    }
}
