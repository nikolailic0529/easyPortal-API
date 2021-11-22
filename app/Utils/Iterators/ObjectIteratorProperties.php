<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

/**
 * @template T
 */
trait ObjectIteratorProperties {
    private ?int            $limit  = null;
    private int             $chunk  = 1000;
    private string|int|null $offset = null;

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function setLimit(?int $limit): static {
        $this->limit = $limit;

        return $this;
    }

    public function getChunkSize(): int {
        return $this->chunk;
    }

    public function setChunkSize(int $chunk): static {
        $this->chunk = $chunk;

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->offset;
    }

    public function setOffset(string|int|null $offset): static {
        $this->offset = $offset;

        return $this;
    }
}
