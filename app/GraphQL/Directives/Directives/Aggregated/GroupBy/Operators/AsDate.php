<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Illuminate\Database\Eloquent\Builder;

class AsDate extends BaseOperator {
    public static function getName(): string {
        return 'asDate';
    }

    protected function getKeyExpression(Builder $builder, string $column): string {
        // We assume that if the column has the `date` cast that it has a `DATE`
        // type in the DB and no cast needed.
        $qualified = $builder->getGrammar()->wrap($builder->qualifyColumn($column));
        $column    = !$builder->getModel()->hasCast($column, ['date', 'immutable_date'])
            ? "DATE_FORMAT({$qualified}, '%Y-%m-%d')"
            : $qualified;

        return $column;
    }
}
