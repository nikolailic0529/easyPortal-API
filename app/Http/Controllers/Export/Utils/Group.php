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

    public function __construct(
        protected mixed $value = null,
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
     * @param int<1, max> $rows
     */
    public function expand(int $rows): static {
        $this->endRow += $rows;

        return $this;
    }

    /**
     * @param int<0, max> $row
     */
    public function update(int $row, mixed $value): ?static {
        // Same value?
        if ($value === $this->value) {
            $this->endRow = $row;

            return null;
        }

        // End
        return $this->end($row, $value);
    }

    /**
     * @param int<0, max> $row
     */
    public function end(int $row, mixed $value): ?static {
        // Switch
        $previous       = $this->isGrouped() ? clone $this : null;
        $this->startRow = $row;
        $this->endRow   = $row;
        $this->value    = $value;

        // Return
        return $previous;
    }
}
