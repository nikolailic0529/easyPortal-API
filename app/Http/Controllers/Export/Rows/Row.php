<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rows;

/**
 * @template TColumn
 */
abstract class Row {
    /**
     * @param list<TColumn> $columns
     * @param int<0, 7>     $level
     * @param int<0, max>   $exported
     */
    public function __construct(
        protected array $columns,
        protected int $level = 0,
        protected int $exported = 1,
    ) {
        // empty
    }

    /**
     * @return list<TColumn>
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * @return int<0, 7>
     */
    public function getLevel(): int {
        return $this->level;
    }

    /**
     * @return int<0, max>
     */
    public function getExported(): int {
        return $this->exported;
    }

    /**
     * @param int<0, max> $exported
     */
    public function setExported(int $exported): static {
        $this->exported = $exported;

        return $this;
    }
}
