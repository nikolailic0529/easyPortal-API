<?php declare(strict_types = 1);

namespace App\Mixins;

use Closure;
use Illuminate\Database\Query\Builder;

class QueryBuilderMixin {
    public function whereMatchAgainst(): Closure {
        return function (string $property, mixed $value): Builder {
            /** @var \Illuminate\Database\Query\Builder $this */
            return $this->whereRaw("MATCH({$property}) AGAINST (?)", [$value]);
        };
    }
}
