<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rows;

class ValueRow extends Row {
    /**
     * @param list<scalar|null> $columns
     * @param int<0, max>       $level
     * @param int<0, max>       $exported
     */
    public function __construct(
        protected array $columns,
        int $level = 0,
        int $exported = 1,
    ) {
        parent::__construct($level, $exported);
    }

    /**
     * @return list<scalar|null>
     */
    public function getColumns(): array {
        return $this->columns;
    }
}
