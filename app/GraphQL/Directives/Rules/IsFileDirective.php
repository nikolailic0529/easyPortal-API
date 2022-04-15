<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\CustomRule;
use App\Rules\File;

class IsFileDirective extends CustomRule {
    protected function getRuleClass(): string {
        return File::class;
    }
}
