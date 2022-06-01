<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Concerns;

use App\Services\DataLoader\Importer\IteratorIterator;
use App\Utils\Eloquent\Model;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;
use Countable;

use function count;
use function is_object;

/**
 * @template TModel of Model
 * @template TItem
 * @template TState of State
 */
trait WithIterator {
    /**
     * @var ObjectIterator<TModel|string>
     */
    private ObjectIterator $iterator;

    /**
     * @param ObjectIterator<TModel|string> $iterator
     */
    public function setIterator(ObjectIterator $iterator): static {
        $this->iterator = $iterator;

        return $this;
    }

    protected function getTotal(State $state): ?int {
        return $this->iterator instanceof Countable
            ? count($this->iterator)
            : null;
    }

    /**
     * @param TState $state
     *
     * @return ObjectIterator<TItem>
     */
    protected function getIterator(State $state): ObjectIterator {
        return new IteratorIterator(
            $this->getExceptionHandler(),
            $this->iterator,
            function (Model|string $model) use ($state): mixed {
                $item = is_object($model) ? $model->getKey() : $model;
                $item = $this->getItem($state, $item);

                return $item;
            },
        );
    }

    /**
     * @param TState $state
     *
     * @return TItem|null
     */
    abstract protected function getItem(State $state, string $item): mixed;
}