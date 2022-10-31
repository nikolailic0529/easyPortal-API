<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use function implode;
use function is_array;

class Asterisk extends Value {
    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $values = [];

        foreach ($item as $value) {
            $value = is_array($value) && isset($value[$this->property])
                ? $this->value($value[$this->property])
                : null;

            if ($value) {
                $values[] = $value;
            }
        }

        $row[$this->index] = implode(', ', $values) ?: null;
    }
}
