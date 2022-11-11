<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rows;

abstract class Row {
    /**
     * @param int<0, max> $level
     * @param int<0, max> $exported
     */
    public function __construct(
        protected int $level = 0,
        protected int $exported = 1,
    ) {
        // empty
    }

    /**
     * @return int<0, max>
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
