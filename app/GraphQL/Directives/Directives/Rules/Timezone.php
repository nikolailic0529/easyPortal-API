<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\LaravelRule;

abstract class Timezone extends LaravelRule {
    protected function getRuleName(): string {
        return 'timezone';
    }
}
