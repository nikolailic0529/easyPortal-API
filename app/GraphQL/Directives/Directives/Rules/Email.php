<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;

abstract class Email extends LaravelRule {
    protected function getRuleName(): string {
        return 'email';
    }
}
