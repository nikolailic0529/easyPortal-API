<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Directive;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\PropertyDirective;

use function implode;

class Property extends PropertyDirective {
    public static function getDirectiveName(): string {
        return implode('', [
            '@',
            Str::camel(Directive::NAME),
            Str::studly(static::getName()),
        ]);
    }

    public function getFieldDescription(): string {
        return 'Property clause.';
    }
}
