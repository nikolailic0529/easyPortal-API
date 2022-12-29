<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Directive;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\BaseOperator;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeDefinition;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;

use function is_null;

class Group implements TypeDefinition {
    public function __construct() {
        // empty
    }

    public static function getTypeName(BuilderInfo $builder, ?string $type, ?bool $nullable): string {
        return Directive::NAME.'TypeGroup';
    }

    public function getTypeDefinitionNode(
        Manipulator $manipulator,
        string $name,
        ?string $type,
        ?bool $nullable,
    ): ?TypeDefinitionNode {
        $node = null;

        if (is_null($type) && is_null($nullable)) {
            $key  = BaseOperator::KEY;
            $node = Parser::objectTypeDefinition(
            /** @lang GraphQL */
                <<<GRAPHQL
                type {$name} {
                    key: String @rename(attribute: "{$key}")
                    count: Int!
                }
                GRAPHQL,
            );
        }

        return $node;
    }
}
