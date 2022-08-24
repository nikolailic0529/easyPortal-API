<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Directive;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\OperatorDirective;

use function implode;

abstract class BaseOperator extends OperatorDirective {
    public static function getDirectiveName(): string {
        return implode('', [
            '@',
            Str::camel(Directive::NAME),
            'Operator',
            Str::studly(static::getName()),
        ]);
    }

    public function isBuilderSupported(object $builder): bool {
        return $builder instanceof EloquentBuilder;
    }
}
