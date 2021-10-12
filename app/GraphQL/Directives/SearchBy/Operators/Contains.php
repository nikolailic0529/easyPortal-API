<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators;

class Contains extends Like {
    public function getName(): string {
        return 'contains';
    }

    protected function getDescription(): string {
        return 'Contains a string.';
    }

    protected function value(string $string): string {
        return "%{$string}%";
    }
}
