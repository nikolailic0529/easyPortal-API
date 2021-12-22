<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * By default Laravel use `SELECT *` if the query contains joins it may lead to
 * overwriting model properties. This trait fixes it by adding a table name to
 * each query.
 *
 * @see https://github.com/laravel/framework/issues/4962
 */
trait QualifiedModelQuery {
    /**
     * @inheritDoc
     */
    public function newEloquentBuilder($query): Builder {
        $builder = parent::newEloquentBuilder($query);

        if (!isset($builder->toBase()->columns)) {
            $builder = $builder->select([$this->qualifyColumn('*')]);
        }

        return $builder;
    }
}
