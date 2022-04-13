<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;

class IsRequiredWithoutDirective extends LaravelRule {
    protected static function arguments(): ?string {
        return <<<'GRAPHQL'
            field: String!
        GRAPHQL;
    }

    protected function getRuleName(): string {
        return 'required_without';
    }
}
