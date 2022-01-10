<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

abstract class LaravelRule extends Rule {
    public function getRule(): string {
        return $this->getRuleName();
    }

    abstract protected function getRuleName(): string;
}
