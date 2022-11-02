<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

class Property extends Value {
    public function __construct(
        protected string $property,
        protected int $index,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $row[$this->index] = $this->value($item[$this->property] ?? null);
    }
}
