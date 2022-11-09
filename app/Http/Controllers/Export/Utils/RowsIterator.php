<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use App\Http\Controllers\Export\Selectors\Root;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Iterator;
use IteratorAggregate;

/**
 * @phpstan-type Column array<int<0, max>, scalar|null>
 *
 * @implements IteratorAggregate<array-key, Column>
 */
class RowsIterator implements IteratorAggregate {
    /**
     * @var Column|null
     */
    protected mixed $currentItem = null;

    /**
     * @var array<Group>
     */
    protected array $currentGroups = [];

    /**
     * @var Column|null
     */
    protected mixed $nextItem = null;

    /**
     * @var array<Group>
     */
    protected array $nextGroups = [];

    /**
     * @param ObjectIterator<array<string,scalar|null>> $iterator
     * @param array<Group>                              $groups
     * @param array<int<0, max>, scalar|null>           $default
     */
    public function __construct(
        protected ObjectIterator $iterator,
        protected Root $valueSelector,
        protected Root $groupSelector,
        array $groups,
        protected array $default,
        protected int $offset,
    ) {
        $this->currentGroups = $groups;
    }

    public function setOffset(int $offset): static {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return int<0, max>
     */
    public function getLevel(): int {
        return 0;
    }

    /**
     * @return array<Group>
     */
    public function getGroups(): array {
        $groups = [];

        foreach ($this->currentGroups as $key => $group) {
            if (($this->nextGroups[$key] ?? null) !== $group && $group->isGrouped()) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * @return Iterator<array-key, Column>
     */
    public function getIterator(): Iterator {
        foreach ($this->iterator as $index => $item) {
            // Process
            $this->nextItem = $this->valueSelector->get($item) + $this->default;
            $columns        = $this->groupSelector->get($item);

            foreach ($this->currentGroups as $key => $group) {
                $this->currentGroups[$key] = $group->update($index, $columns[$group->getColumn()] ?? null);
                $this->nextGroups[$key]    = $group;
            }

            // Emit
            if ($this->currentItem !== null) {
                yield $this->currentItem;
            }

            // Set
            $this->currentItem   = $this->nextItem;
            $this->currentGroups = $this->nextGroups;
        }

        $this->nextItem   = null;
        $this->nextGroups = [];

        if ($this->currentItem !== null) {
            yield $this->currentItem;
        }
    }
}
