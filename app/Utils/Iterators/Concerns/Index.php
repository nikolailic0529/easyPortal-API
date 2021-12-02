<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

trait Index {
    private int $index = 0;

    public function getIndex(): int {
        return $this->index;
    }

    public function setIndex(int $index): static {
        $this->index = $index;

        return $this;
    }
}
