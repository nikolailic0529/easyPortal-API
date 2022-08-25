<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Illuminate\Database\Grammar;

class AsString extends BaseOperator {
    public static function getName(): string {
        return 'asString';
    }

    protected function getKeyExpression(Grammar $grammar, string $column): string {
        return $grammar->wrap($column);
    }
}
