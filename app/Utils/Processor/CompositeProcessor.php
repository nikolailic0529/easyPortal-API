<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Contracts\Limitable;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Contracts\Offsetable;
use App\Utils\Iterators\ObjectsIterator;
use Throwable;

use function array_map;
use function array_merge;
use function count;

/**
 * The Processor to combine other processors.
 *
 * @extends Processor<CompositeOperation, null, CompositeState>
 */
abstract class CompositeProcessor extends Processor {
    /**
     * @var array<int, CompositeOperation>
     */
    private array $operations;

    // <editor-fold desc="Process">
    // =========================================================================
    protected function getIterator(State $state): ObjectIterator {
        return new ObjectsIterator($this->getExceptionHandler(), $this->getOperations());
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return null;
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        $store     = new CompositeStore($state);
        $processor = $item->getProcessor($state);

        if ($processor instanceof Limitable) {
            $processor = $processor->setLimit(null);
        }

        if ($processor instanceof Offsetable) {
            $processor = $processor->setOffset(null);
        }

        $processor
            ->setStore($store)
            ->setChunkSize($this->getChunkSize())
            ->onChange(function () use ($processor, $state): void {
                $this->saveState($state);

                if ($this->isStopped()) {
                    $processor->stop();
                }
            })
            ->onReport(function () use ($state): void {
                $this->notifyOnReport($state);
            })
            ->onProcess(function () use ($state): void {
                $this->notifyOnProcess($state);
            })
            ->start();
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        throw $exception;
    }

    protected function getTotal(State $state): ?int {
        return count($this->getOperations());
    }

    /**
     * @return array<int, CompositeOperation>
     */
    final protected function getOperations(): array {
        if (!isset($this->operations)) {
            $this->operations = $this->operations();
        }

        return $this->operations;
    }

    /**
     * @return array<int, CompositeOperation>
     */
    abstract protected function operations(): array;
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
            'operations' => $this->getStateOperations(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getStateOperations(): array {
        $operations = $this->getOperations();
        $operations = array_map(
            static function (CompositeOperation $operation): array {
                return [
                    'name'  => $operation->getName(),
                    'state' => null,
                ];
            },
            $operations,
        );

        return $operations;
    }
    // </editor-fold>
}
