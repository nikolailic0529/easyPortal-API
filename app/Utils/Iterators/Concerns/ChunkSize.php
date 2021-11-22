<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

trait ChunkSize {
    private int $chunkSize = 1000;

    public function getChunkSize(): int {
        return $this->chunkSize;
    }

    public function setChunkSize(int $chunk): static {
        $this->chunkSize = $chunk;

        return $this;
    }
}
