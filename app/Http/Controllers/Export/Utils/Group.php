<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

class Group {
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

    public function isGrouped(): bool {
        return $this->startRow !== $this->endRow;
    }

    /**
     * @param int<1, max> $rows
     */
    public function move(int $rows): static {
        $this->startRow += $rows;
        $this->endRow   += $rows;

        return $this;
    }

    /**
     * @param int<0, max> $row
     */
    public function update(int $row, mixed $value): static {
        // Same value?
        if ($value === $this->value) {
            $this->endRow = $row;

            return $this;
        }

        // Previous
        $previous         = clone $this;
        $previous->endRow = $row > 0 ? $row - 1 : 0;

        // Update
        $this->startRow = $row;
        $this->endRow   = $row;
        $this->value    = $value;

        // Return
        return $previous;
    }
}
