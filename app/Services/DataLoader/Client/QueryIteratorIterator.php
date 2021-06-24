<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use Closure;
use Generator;
use InvalidArgumentException;

use function explode;
use function gettype;
use function is_null;
use function is_string;
use function min;
use function sprintf;

class QueryIteratorIterator implements QueryIterator {
    protected ?Closure $beforeChunk = null;
    protected ?Closure $afterChunk  = null;
    protected ?string  $current     = null;
    protected ?int     $limit       = null;
    protected int      $chunk       = 1000;

    /**
     * @param array<string,\App\Services\DataLoader\Client\QueryIterator> $iterators
     */
    public function __construct(
        protected array $iterators,
    ) {
        $this->setChunkSize($this->chunk);
        $this->setOffset(null);
    }

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

    public function getOffset(): string|null {
        $offset = null;

        if ($this->current) {
            $offset   = $this->current;
            $iterator = $this->iterators[$this->current] ?? null;

            if ($iterator?->getOffset() !== null) {
                $offset = "{$offset}@{$iterator->getOffset()}";
            }
        }

        return $offset;
    }

    /**
     * @param string|null $offset in the following format: `<name>[@<offset>]`,
     *                            where `<offset>` the offset for the iterator
     *                            with index `<name>`.
     *
     * @return $this
     */
    public function setOffset(string|int|null $offset): static {
        // Valid?
        if (!is_string($offset) && !is_null($offset)) {
            throw new InvalidArgumentException(sprintf(
                'The `$offset` must be `string` or `null`, `%s` given',
                gettype($offset),
            ));
        }

        // Parse
        $newCurrent = null;
        $newOffset  = null;

        if ($offset !== null) {
            $parts      = explode('@', $offset, 2);
            $newCurrent = $parts[0];
            $newOffset  = $parts[1] ?? null;

            if (!isset($this->iterators[$newCurrent])) {
                throw new InvalidArgumentException(sprintf(
                    'The `$offset` is not valid, iterator `%s` is unknown.',
                    $newCurrent,
                ));
            }
        }

        // Reset all
        foreach ($this->iterators as $iterator) {
            $iterator->setOffset(null);
        }

        // Update
        $this->current = $newCurrent;

        if (isset($this->iterators[$newCurrent])) {
            $this->iterators[$newCurrent]->setOffset($newOffset);
        }

        // Return
        return $this;
    }

    public function onBeforeChunk(?Closure $closure): static {
        $this->beforeChunk = $closure;

        return $this;
    }

    public function onAfterChunk(?Closure $closure): static {
        $this->afterChunk = $closure;

        return $this;
    }

    public function getIterator(): Generator {
        $index     = 0;
        $limit     = $this->getLimit();
        $chunk     = $limit ? min($limit, $this->getChunkSize()) : $this->getChunkSize();
        $iterating = false;

        foreach ($this->iterators as $key => $iterator) {
            // Iterating?
            $iterating = $iterating || $this->current === null || $this->current === $key;

            if (!$iterating) {
                continue;
            }

            // Update state
            $this->current = $key;

            // Prepare
            $iterator->setChunkSize($chunk);
            $iterator->onBeforeChunk($this->beforeChunk);
            $iterator->onAfterChunk($this->afterChunk);

            // Iterate
            foreach ($iterator as $item) {
                yield $index++ => $item;

                if ($limit && $index >= $limit) {
                    break 2;
                }
            }
        }
    }
}
