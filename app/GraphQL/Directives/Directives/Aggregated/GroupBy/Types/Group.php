<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators\BaseOperator;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeDefinition;

use function is_null;

class Group implements TypeDefinition {
    public function __construct() {
        // empty
    }

    public static function getName(): string {
        return 'Group';
    }

    public function getTypeDefinitionNode(
        string $name,
        string $scalar = null,
        bool $nullable = null,
    ): ?TypeDefinitionNode {
        $type = null;

        if (is_null($scalar) && is_null($nullable)) {
            $key  = BaseOperator::KEY;
            $type = Parser::objectTypeDefinition(
            /** @lang GraphQL */
                <<<GRAPHQL
                type {$name} {
                    key: String @rename(attribute: "{$key}")
                    count: Int!
                }
                GRAPHQL,
            );
        }

        return $type;
    }
}
