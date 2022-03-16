<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

use InvalidArgumentException;

class Relation extends Property {
    /**
     * @param array<string,Property|array<mixed>> $properties
     */
    public function __construct(
        string $name,
        protected array $properties,
    ) {
        parent::__construct($name);

        if (!$this->properties) {
            throw new InvalidArgumentException('Properties cannot be empty.');
        }
    }

    /**
     * @return array<string,Property|array<mixed>>
     */
    public function getProperties(): array {
        return $this->properties;
    }
}
