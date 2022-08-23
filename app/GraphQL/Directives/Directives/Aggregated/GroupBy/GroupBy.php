<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

abstract class GroupBy extends BaseDirective {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            directive @aggregatedGroupBy on ARGUMENT_DEFINITION
            GRAPHQL;
    }

    // fixme(GraphQL): Generate types
    // fixme(GraphQL): Update builder
}
