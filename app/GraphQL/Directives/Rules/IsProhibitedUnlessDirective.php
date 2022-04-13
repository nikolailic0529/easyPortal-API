<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;

class IsProhibitedUnlessDirective extends LaravelRule {
    protected static function arguments(): ?string {
        return <<<'GRAPHQL'
            field: String!
            value: String
        GRAPHQL;
    }

    protected function getRuleName(): string {
        return 'prohibited_unless';
    }
}
