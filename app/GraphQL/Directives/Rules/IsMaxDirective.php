<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;

class IsMaxDirective extends LaravelRule {
    protected static function arguments(): ?string {
        return <<<'GRAPHQL'
            value: Int!
        GRAPHQL;
    }

    protected function getRuleName(): string {
        return 'max';
    }
}
