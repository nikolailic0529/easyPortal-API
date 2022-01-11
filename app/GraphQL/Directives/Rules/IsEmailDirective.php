<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;

class IsEmailDirective extends LaravelRule {
    protected function getRuleName(): string {
        return 'email';
    }
}
