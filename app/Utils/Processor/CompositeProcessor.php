<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Contracts\Limitable;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Contracts\Offsetable;
use App\Utils\Iterators\ObjectsIterator;
use Throwable;

use function array_merge;
use function count;

/**
 * The Processor to combine other processors.
 *
 * @template TState of \App\Utils\Processor\CompositeState
 *
 * @extends Processor<CompositeOperation<TState>, null, TState>
 */
abstract class CompositeProcessor extends Processor {
    /**
     * @var array<int, CompositeOperation<TState>>
     */
    private array $operations;

    // <editor-fold desc="Process">
    // =========================================================================
    protected function getIterator(State $state): ObjectIterator {
        return new ObjectsIterator($this->getExceptionHandler(), $this->getOperations($state));
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return null;
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        $state       = $state->setCurrentOperationName($item->getName());
        $store       = new CompositeStore($state);
        $handler     = $item->getHandler();
        $processor   = $item->getProcessor($state);
        $synchronize = static function (State $current) use ($state): void {
            $state->setCurrentOperationState($current);
        };

        if ($processor instanceof Limitable) {
            $processor = $processor->setLimit(null);
        }

        if ($processor instanceof Offsetable) {
            $processor = $processor->setOffset(null);
        }

        $result = $processor
            ->setStore($store)
            ->setChunkSize(parent::getChunkSize())
            ->onInit($synchronize)
            ->onChange(function (State $current) use ($processor, $state, $synchronize): void {
                $this->saveState($state);

                $synchronize($current);

                if ($this->isStopped()) {
                    $processor->stop();
                }
            })
            ->onReport(function (State $current) use ($state, $synchronize): void {
                $synchronize($current);

                $this->notifyOnReport($state);
            })
            ->onProcess(function (State $current) use ($state, $synchronize): void {
                $synchronize($current);

                $this->notifyOnProcess($state);
            })
            ->start();

        if ($handler && !$processor->isStopped()) {
            $handler($state, $result);
        }
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        throw $exception;
    }

    protected function init(State $state, ObjectIterator $iterator): void {
        parent::init($state, $iterator->setChunkSize(1));
    }

    protected function finish(State $state): void {
        parent::finish($state->resetCurrentOperation());
    }

    protected function getTotal(State $state): ?int {
        return count($this->getOperations($state));
    }

    /**
     * @param TState $state
     *
     * @return array<int, CompositeOperation<TState>>
     */
    final protected function getOperations(CompositeState $state): array {
        if (!isset($this->operations)) {
            $this->operations = $this->operations($state);
        }

        return $this->operations;
    }

    /**
     * @param TState $state
     *
     * @return array<int, CompositeOperation<TState>>
     */
    abstract protected function operations(CompositeState $state): array;
    //</editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new CompositeState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'operations' => [],
        ]);
    }
    // </editor-fold>
}
