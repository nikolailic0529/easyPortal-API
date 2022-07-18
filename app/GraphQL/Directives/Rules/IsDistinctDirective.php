<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;

class IsDistinctDirective extends LaravelRule {
    protected function getRuleName(): string {
        return 'distinct';
    }
}
