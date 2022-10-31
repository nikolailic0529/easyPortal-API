<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;

use function is_array;

class Group implements Selector {
    /**
     * @param array<Selector> $selectors
     */
    public function __construct(
        protected string $field,
        protected array $selectors,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $value = $item[$this->field] ?? null;

        if (is_array($value)) {
            foreach ($this->selectors as $selector) {
                $selector->fill($value, $row);
            }
        }
    }
}
