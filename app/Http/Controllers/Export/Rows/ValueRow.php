<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rows;

class ValueRow extends Row {
    /**
     * @param array<int<0, max>, scalar|null> $columns
     */
    public function __construct(
        array $columns,
        protected int $level = 0,
    ) {
        parent::__construct($columns);
    }

    public function getLevel(): int {
        return $this->level;
    }
}
