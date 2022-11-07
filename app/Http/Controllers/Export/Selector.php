<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

interface Selector {
    /**
     * @param array<scalar|null|array<scalar|null>> $item
     * @param array<scalar|null>                    $row
     */
    public function fill(array $item, array &$row): void;

    /**
     * @return array<string>
     */
    public function getSelectors(): array;
}
