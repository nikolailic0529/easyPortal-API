<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\CustomRule;
use App\Rules\Image as ImageRule;

abstract class Image extends CustomRule {
    protected function getRuleClass(): string {
        return ImageRule::class;
    }
}
