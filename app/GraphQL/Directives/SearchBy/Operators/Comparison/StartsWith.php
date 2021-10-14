<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators\Comparison;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class StartsWith extends Like {
    public function getName(): string {
        return 'startsWith';
    }

    protected function getDescription(): string {
        return 'Starts with a string.';
    }

    protected function value(string $string): string {
        return "{$string}%";
    }

    protected function match(EloquentBuilder|QueryBuilder $builder, string $property): bool {
        // MySQL can use index for `abc%` => fulltext search not needed.
        return false;
    }
}
