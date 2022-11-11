<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rows;

class GroupEndRow extends Row {
    /**
     * @param int<0, max> $column
     * @param int<0, max> $startRow
     * @param int<0, max> $endRow
     * @param int<0, max> $level
     * @param int<0, max> $exported
     */
    public function __construct(
        protected int $column,
        protected int $startRow,
        protected int $endRow,
        int $level = 0,
        int $exported = 1,
    ) {
        parent::__construct($level, $exported);
    }

    /**
     * @return int<0, max>
     */
    public function getColumn(): int {
        return $this->column;
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
}
