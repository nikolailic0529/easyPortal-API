<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\Properties;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Generator;
use InvalidArgumentException;

use function explode;
use function gettype;
use function is_null;
use function is_string;
use function min;
use function sprintf;

/**
 * @template TItem
 *
 * @implements ObjectIterator<TItem>
 */
class ObjectIteratorsIterator implements ObjectIterator {
    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\InitialState<TItem>
     */
    use InitialState;
    use Properties;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\Subjects<TItem>
     */
    use Subjects {
        chunkLoaded as private;
        chunkProcessed as private;
    }

    protected ?string $current = null;

    /**
     * @param array<string,ObjectIterator<TItem>> $iterators
     */
    public function __construct(
        protected array $iterators,
    ) {
        $this->setChunkSize($this->getChunkSize());
        $this->setOffset(null);
        $this->setIndex(0);
    }

    public function getCount(): ?int {
        return null;
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
     * @param string|int|null $offset in the following format: `<name>[@<offset>]`,
     *                                where `<offset>` the offset for the iterator
     *                                with index `<name>`.
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
            $newOffset  = ($parts[1] ?? null) ?: null;

            if (!isset($this->iterators[$newCurrent])) {
                throw new InvalidArgumentException(sprintf(
                    'The `$offset` is not valid, iterator `%s` is unknown.',
                    $newCurrent,
                ));
            }
        }

        // Reset all
        foreach ($this->iterators as $iterator) {
            $iterator->setIndex(0);
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

    public function getIterator(): Generator {
        $index     = $this->getIndex();
        $limit     = $this->getLimit();
        $chunk     = $limit ? min($limit, $this->getChunkSize()) : $this->getChunkSize();
        $after     = $this->getOnAfterChunkDispatcher()->getObservers();
        $before    = $this->getOnBeforeChunkDispatcher()->getObservers();
        $iterating = false;

        try {
            $this->init();

            foreach ($this->iterators as $key => $iterator) {
                // Iterating?
                $iterating = $iterating || $this->current === null || $this->current === $key;

                if (!$iterating) {
                    continue;
                }

                // Update state
                $this->current = $key;

                // Prepare
                $iterator->setLimit(null);
                $iterator->setChunkSize($chunk);

                foreach ($before as $observer) {
                    $iterator->onBeforeChunk($observer);
                }

                foreach ($after as $observer) {
                    $iterator->onAfterChunk($observer);
                }

                if ($limit) {
                    $iterator->setLimit($limit - $index);
                }

                // Iterate
                foreach ($iterator as $item) {
                    yield $index++ => $item;

                    $this->setIndex($index);
                }
            }
        } finally {
            $this->finish();
        }
    }
}
