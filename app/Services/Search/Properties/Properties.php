<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Properties extends Property {
    /**
     * @param non-empty-array<string,Property> $properties
     */
    public function __construct(
        protected array $properties,
    ) {
        parent::__construct('properties');
    }

    /**
     * @return non-empty-array<string,Property>
     */
    public function getProperties(): array {
        return $this->properties;
    }
}
