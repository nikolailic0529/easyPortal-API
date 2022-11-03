<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;

use function implode;
use function is_array;
use function reset;

class Asterisk extends Value {
    /**
     * @param int<0, max> $index
     */
    public function __construct(
        protected Selector $selector,
        protected int $index,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $values = [];

        foreach ($item as $child) {
            if (!is_array($child)) {
                continue;
            }

            $value = [];

            $this->selector->fill($child, $value);

            $value = $this->value(reset($value));

            if ($value) {
                $values[] = $value;
            }
        }

        $row[$this->index] = implode(', ', $values) ?: null;
    }
}
