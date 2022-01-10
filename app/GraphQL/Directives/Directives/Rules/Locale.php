<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\CustomRule;
use App\Rules\Locale as LocaleRule;

abstract class Locale extends CustomRule {
    protected function getRuleClass(): string {
        return LocaleRule::class;
    }
}
