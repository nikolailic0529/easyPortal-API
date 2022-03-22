<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Contracts;

use Closure;
use IteratorAggregate;

/**
 * The ObjectIterator is specially designed to process a huge amount of items
 * from different sources. It supports iteration restoration from the specified
 * offset, provides chunks support, and should implement error handling to avoid
 * stopping iteration if one item failed.
 *
 * @template T
 *
 * @extends IteratorAggregate<T>
 */
interface ObjectIterator extends IteratorAggregate {
    public function getIndex(): int;

    public function setIndex(int $index): static;

    public function getLimit(): ?int;

    public function setLimit(?int $limit): static;

    public function getChunkSize(): int;

    public function setChunkSize(?int $chunk): static;

    public function getOffset(): string|int|null;

    public function setOffset(string|int|null $offset): static;

    /**
     * Sets the closure that will be called before iteration.
     *
     * @param Closure(): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onInit(?Closure $closure): static;

    /**
     * Sets the closure that will be called after iteration.
     *
     * @param Closure(): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onFinish(?Closure $closure): static;

    /**
     * Sets the closure that will be called after received each non-empty chunk.
     *
     * @param Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onBeforeChunk(?Closure $closure): static;

    /**
     * Sets the closure that will be called after non-empty chunk processed.
     *
     * @param Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onAfterChunk(?Closure $closure): static;
}
