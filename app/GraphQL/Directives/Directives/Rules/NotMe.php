<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\CustomRule;
use App\Rules\UserNotMe;

class NotMe extends CustomRule {
    protected function getRuleClass(): string {
        return UserNotMe::class;
    }
}
