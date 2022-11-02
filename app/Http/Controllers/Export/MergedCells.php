<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

class MergedCells {
    /**
     * @var int<0, max>
     */
    protected int $startRow = 0;
    /**
     * @var int<0, max>
     */
    protected int $endRow = 0;

    protected mixed $value = null;

    /**
     * @param int<0, max> $column
     */
    public function __construct(
        protected int $column,
    ) {
        // empty
    }

    /**
     * @return int<0, max>
     */
    public function getStartRow(): int {
        return $this->startRow;
    }

    /**
     * @return int<0, max>
     */
    public function getEndRow(): int {
        return $this->endRow;
    }

    /**
     * @return int<0, max>
     */
    public function getColumn(): int {
        return $this->column;
    }

    public function isMerged(): bool {
        return $this->startRow !== $this->endRow;
    }

    /**
     * @param int<0, max>   $row
     */
    public function merge(int $row, mixed $value): ?static {
        // Same value?
        if ($value === $this->value) {
            $this->endRow = $row;

            return null;
        }

        // Previous group
        $merged = null;

        if ($this->isMerged()) {
            $merged = clone $this;
        }

        // Reset
        $this->startRow = $row;
        $this->endRow   = $row;
        $this->value    = $value;

        // Return
        return $merged;
    }
}
