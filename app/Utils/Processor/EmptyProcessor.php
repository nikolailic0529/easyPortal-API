<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use Throwable;

/**
 * @extends Processor<mixed, null, State>
 */
final class EmptyProcessor extends Processor {
    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return null;
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        // empty
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        // empty
    }

    protected function getTotal(State $state): ?int {
        return 0;
    }

    protected function getIterator(State $state): ObjectIterator {
        return new ObjectsIterator($this->getExceptionHandler(), []);
    }
}
