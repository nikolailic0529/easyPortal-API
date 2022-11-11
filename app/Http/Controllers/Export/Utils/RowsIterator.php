<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use App\Http\Controllers\Export\Selectors\Root;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Iterator;
use IteratorAggregate;
use LogicException;

/**
 * @phpstan-type Column array<int<0, max>, scalar|null>
 *
 * @implements IteratorAggregate<int, Column>
 */
class RowsIterator implements IteratorAggregate {
    /**
     * @var Column|null
     */
    private mixed $currentItem = null;

    /**
     * @var array<int<0, max>, Group>
     */
    private array $currentGroups = [];

    /**
     * @var Column|null
     */
    private mixed $nextItem = null;

    /**
     * @var array<int<0, max>, Group>
     */
    private array $nextGroups = [];

    /**
     * @var int<0, max>
     */
    private int $level = 0;

    /**
     * @param ObjectIterator<array<string,scalar|null>|null> $iterator
     * @param array<int<0, max>, Group>                      $groups
     * @param array<int<0, max>, scalar|null>                $default
     * @param int<0, max>                                    $offset
     */
    public function __construct(
        private ObjectIterator $iterator,
        private Root $valueSelector,
        private Root $groupSelector,
        array $groups,
        private array $default,
        private int $offset,
    ) {
        $this->nextGroups = $groups;
    }

    /**
     * @param int<0, max> $offset
     */
    public function setOffset(int $offset): static {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return int<0, max>
     */
    public function getLevel(): int {
        return $this->level;
    }

    /**
     * @return array<int<0, max>, Group>
     */
    public function getGroups(): array {
        return $this->currentGroups;
    }

    /**
     * @return Iterator<int, Column>
     */
    public function getIterator(): Iterator {
        $index = $this->offset;

        foreach ($this->iterator as $item) {
            // Process
            $item           = (array) $item;
            $this->nextItem = $this->valueSelector->get($item) + $this->default;
            $this->offset   = 0;
            $this->level    = 0;
            $columns        = $this->groupSelector->get($item);
            $previous       = null;

            foreach ($this->nextGroups as $key => $group) {
                $ended       = $previous === null || $previous->isGrouped()
                    ? $group->update($index, $columns[$key] ?? null)
                    : $group->end($index, $columns[$key] ?? null);
                $previous    = $group;
                $this->level = ($ended ?? $group)->isGrouped()
                    ? $key + 1
                    : $this->level;

                if ($ended) {
                    $this->currentGroups[$key] = $ended;
                } else {
                    unset($this->currentGroups[$key]);
                }
            }

            // Emit
            if ($this->currentItem !== null) {
                yield $this->currentItem;

                if ($this->offset > 1) {
                    foreach ($this->nextGroups as $group) {
                        if ($group->getStartRow() === $index) {
                            $group->move($this->offset - 1);
                        } else {
                            $group->expand($this->offset - 1);
                        }
                    }

                    $index += $this->offset - 1;
                } elseif ($this->offset <= 0) {
                    throw new LogicException('Offset should be greater than `0`.');
                } else {
                    // =1: no action
                }
            }

            // Next
            $this->currentItem = $this->nextItem;
            $this->offset      = 1;
            $index++;
        }

        // Last
        $this->level = 0;

        foreach ($this->nextGroups as $key => $group) {
            if ($group->isGrouped()) {
                $this->level               = $key + 1;
                $this->currentGroups[$key] = $group;
            } else {
                unset($this->currentGroups[$key]);
            }
        }

        $this->nextItem   = null;
        $this->nextGroups = [];

        if ($this->currentItem !== null) {
            yield $this->currentItem;
        }
    }
}
