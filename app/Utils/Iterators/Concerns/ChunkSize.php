<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

trait ChunkSize {
    private ?int $chunkSize = null;

    public function getChunkSize(): int {
        return $this->chunkSize ?? 500;
    }

    public function setChunkSize(?int $chunk): static {
        $this->chunkSize = $chunk;

        return $this;
    }
}
