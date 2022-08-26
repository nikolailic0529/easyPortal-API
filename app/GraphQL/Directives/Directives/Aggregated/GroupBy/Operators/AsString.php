<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Illuminate\Database\Eloquent\Builder;

class AsString extends BaseOperator {
    public static function getName(): string {
        return 'asString';
    }

    protected function getKeyExpression(Builder $builder, string $column): string {
        return $builder->getGrammar()->wrap($builder->qualifyColumn($column));
    }
}
