<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Relation extends Property {
    /**
     * @param non-empty-array<string,Property> $properties
     */
    public function __construct(
        string $name,
        protected array $properties,
    ) {
        parent::__construct($name);
    }

    /**
     * @return non-empty-array<string,Property>
     */
    public function getProperties(): array {
        return $this->properties;
    }
}
