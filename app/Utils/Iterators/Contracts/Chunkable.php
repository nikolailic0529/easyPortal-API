<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Contracts;

interface Chunkable {
    public function getChunkSize(): int;

    public function setChunkSize(?int $chunk): static;
}
