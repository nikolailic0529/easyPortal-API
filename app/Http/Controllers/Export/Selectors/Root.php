<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;

use function in_array;

class Root implements Selector {
    /**
     * @param array<Selector> $selectors
     */
    public function __construct(
        protected array $selectors = [],
    ) {
        // empty
    }

    /**
     * @param array<scalar|null|array<scalar|null>> $item
     *
     * @return  array<int<0, max>, scalar|null>
     */
    public function get(array $item): array {
        $row = [];

        $this->fill($item, $row);

        return $row;
    }

    public function add(Selector $selector): static {
        if (!in_array($selector, $this->selectors, true)) {
            $this->selectors[] = $selector;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        foreach ($this->selectors as $selector) {
            $selector->fill($item, $row);
        }
    }
}
