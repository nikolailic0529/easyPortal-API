<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

trait Offset {
    private string|int|null $offset = null;

    public function getOffset(): string|int|null {
        return $this->offset;
    }

    public function setOffset(string|int|null $offset): static {
        $this->offset = $offset;

        return $this;
    }
}
