<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use LastDragon_ru\LaraASP\GraphQL\AstManipulator;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;
use LogicException;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Scout\SearchDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;

use function json_encode;
use function sprintf;

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
        ObjectTypeDefinitionNode $parent,
        FieldDefinitionNode $field,
    ): void {
        // Add *Aggregate field
        $parent->fields[] = $this->getAggregateField($parent, $field);

        // Add limit/offset arguments
        $field->arguments[] = $this->getLimitField();
        $field->arguments[] = $this->getOffsetField();
    }

    protected function getAggregateField(
        ObjectTypeDefinitionNode $parent,
        FieldDefinitionNode $field,
    ): FieldDefinitionNode {
        // Clone
        $aggregated = $field->cloneDeep();

        if (!($aggregated instanceof FieldDefinitionNode)) {
            throw new LogicException('Failed to clone `$node`.');
        }

        // https://github.com/webonyx/graphql-php/issues/988
        $aggregated->arguments = clone $aggregated->arguments;

        // Cleanup arguments
        foreach ($aggregated->arguments as $key => $argument) {
            $isArgBuilderDirective = (bool) $this->getNodeDirective($argument, ArgBuilderDirective::class);
            $isSearchDirective     = (bool) $this->getNodeDirective($argument, SearchDirective::class);
            $isSortByDirective     = (bool) $this->getNodeDirective($argument, SortByDirective::class);

            if ($isSortByDirective || (!$isArgBuilderDirective && !$isSearchDirective)) {
                unset($aggregated->arguments[$key]);
            }
        }

        // Cleanup directives
        foreach ($aggregated->directives as $key => $directive) {
            if ($directive instanceof Paginated) {
                unset($aggregated->directives[$key]);
            }
        }

        // Set type
        $typeName         = $this->getAggregatedFieldType($field);
        $aggregated->type = Parser::typeReference($typeName);

        // Set name
        $fieldName               = "{$this->getNodeName($field)}Aggregated";
        $aggregated->name->value = $fieldName;

        // Field exists?
        $existing = Arr::first($parent->fields, function (FieldDefinitionNode $field) use ($fieldName): bool {
            return $this->getNodeName($field) === $fieldName;
        });

        if ($existing) {
            throw new LogicException(sprintf(
                'Field `%s` already defined in `%s`.',
                $fieldName,
                $this->getNodeTypeFullName($aggregated),
            ));
        }

        // Return
        return $aggregated;
    }

    protected function getAggregatedFieldType(FieldDefinitionNode $node): string {
        $typeName = "{$this->getNodeTypeName($node)}Aggregated";

        if ($this->isTypeDefinitionExists($typeName)) {
            throw new LogicException(sprintf(
                'Type `%s` already defined.',
                $typeName,
            ));
        }

        $this->addTypeDefinition(Parser::objectTypeDefinition(
            <<<DEF
            """
            Aggregated query for {$this->getNodeTypeFullName($node)}.
            """
            type {$typeName} {
                count: Int!
            }
            DEF,
        ));

        return $typeName;
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
            "max:{$max}",
        ]);

        return Parser::inputValueDefinition(
            <<<DEF
            "Maximum value is {$max}."
            limit: Int! {$value} @rules(apply: {$rules})
            DEF,
        );
    }
}
