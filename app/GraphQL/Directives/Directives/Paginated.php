<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Scout\ScoutBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;

use function json_encode;

class Paginated extends BaseDirective implements FieldManipulator, ArgBuilderDirective, ScoutBuilderDirective {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Adds offset-based pagination for the field.
            """
            directive @paginated on FIELD_DEFINITION
            GRAPHQL;
    }

    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        $fieldDefinition->arguments[] = $this->getLimitField();
        $fieldDefinition->arguments[] = $this->getOffsetField();
    }

    /**
     * @inheritdoc
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        if (isset($value['limit'])) {
            $builder = $builder->limit($value['limit']);

            if (isset($value['offset'])) {
                $builder = $builder->offset($value['offset']);
            }
        }

        return $builder;
    }

    public function handleScoutBuilder(ScoutBuilder $builder, mixed $value): ScoutBuilder {
        // fixme: there is no "offset" :(

        return $builder;
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
