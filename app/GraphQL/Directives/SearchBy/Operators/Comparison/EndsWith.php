<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators\Comparison;

class EndsWith extends Like {
    public function getName(): string {
        return 'endsWith';
    }

    protected function getDescription(): string {
        return 'Ends with a string.';
    }

    protected function value(string $string): string {
        return "%{$string}";
    }
}
