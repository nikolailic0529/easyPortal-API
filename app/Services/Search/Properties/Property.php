<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

abstract class Property {
    public function __construct(
        protected string $name,
        protected bool $searchable = false,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }

    public function isSearchable(): bool {
        return $this->searchable;
    }

    abstract public function hasKeyword(): bool;
}
