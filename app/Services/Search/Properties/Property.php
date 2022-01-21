<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

abstract class Property {
    public function __construct(
        protected string $name,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }
}
