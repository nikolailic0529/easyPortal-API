<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use IteratorAggregate;

/**
 * @template T
 *
 * @extends \IteratorAggregate<T>
 */
interface ObjectIterator extends IteratorAggregate {
    public function getLimit(): ?int;

    public function setLimit(?int $limit): static;

    public function getChunkSize(): int;

    public function setChunkSize(int $chunk): static;

    public function getOffset(): string|int|null;

    public function setOffset(string|int|null $offset): static;

    /**
     * Sets the closure that will be called after received each non-empty chunk.
     *
     * @param \Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onBeforeChunk(?Closure $closure): static;

    /**
     * Sets the closure that will be called after non-empty chunk processed.
     *
     * @param \Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onAfterChunk(?Closure $closure): static;
}
