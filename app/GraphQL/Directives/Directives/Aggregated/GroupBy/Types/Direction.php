<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Directive;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\Parser;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeDefinition;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;

use function is_null;

class Direction implements TypeDefinition {
    public function __construct() {
        // empty
    }

    public static function getTypeName(BuilderInfo $builder, ?string $type, ?bool $nullable): string {
        return Directive::NAME.'TypeDirection';
    }

    public function getTypeDefinitionNode(
        Manipulator $manipulator,
        string $name,
        ?string $type,
        ?bool $nullable,
    ): ?TypeDefinitionNode {
        $node = null;

        if (is_null($type) && is_null($nullable)) {
            $node = Parser::enumTypeDefinition(
            /** @lang GraphQL */
                <<<GRAPHQL
                """
                Sort direction.
                """
                enum {$name} {
                    asc
                    desc
                }
                GRAPHQL,
            );
        }

        return $node;
    }
}
