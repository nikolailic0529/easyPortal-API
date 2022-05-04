<?php declare(strict_types = 1);

namespace App\Utils\Processor\Contracts;

use App\Utils\Iterators\Contracts\Chunkable;
use App\Utils\Iterators\Contracts\Limitable;
use App\Utils\Iterators\Contracts\Offsetable;
use App\Utils\Processor\State;
use Closure;

/**
 * The Processor is specially designed to process a huge amount of items with
 * ability to stop and resume the processing.
 *
 * @see Limitable
 * @see Offsetable
 *
 * @template TItem
 * @template TChunkData
 * @template TState of State
 */
interface Processor extends Chunkable {
    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function isStopped(): bool;

    public function isRunning(): bool;

    /**
     * @return TState|null
     */
    public function getState(): ?State;

    public function getStore(): ?StateStore;

    public function setStore(?StateStore $store): static;
    // </editor-fold>

    // <editor-fold desc="Process">
    // =========================================================================
    public function start(): bool;

    public function stop(): void;

    public function reset(): void;
    // </editor-fold>

    // <editor-fold desc="Events">
    // =========================================================================
    /**
     * @param Closure(TState): void|null $closure
     */
    public function onInit(?Closure $closure): static;

    /**
     * @param Closure(TState): void|null $closure
     */
    public function onChange(?Closure $closure): static;

    /**
     * @param Closure(TState): void|null $closure
     */
    public function onFinish(?Closure $closure): static;

    /**
     * @param Closure(TState): void|null $closure
     */
    public function onProcess(?Closure $closure): static;

    /**
     * @param Closure(TState): void|null $closure
     */
    public function onReport(?Closure $closure): static;
    // </editor-fold>
}
