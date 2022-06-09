<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Importer\Concerns\WithObjectId;
use App\Services\DataLoader\Loader\Concerns\WithLoaderState;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

/**
 * @extends Processor<string, null, LoaderState>
 */
class CallbackLoader extends Processor {
    use WithLoaderState;
    use WithObjectId;

    /**
     * @var Closure(Dispatcher, Client, string): void
     */
    private Closure $callback;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        protected Client $client,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @param Closure(Dispatcher, Client, string): void $callback
     */
    public function setCallback(Closure $callback): static {
        $this->callback = $callback;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Processor">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return null;
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        ($this->callback)($this->getDispatcher(), $this->client, $item);
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        $this->getExceptionHandler()->report($exception);
    }

    protected function getTotal(State $state): ?int {
        return 1;
    }

    protected function getIterator(State $state): ObjectIterator {
        return new ObjectsIterator([$state->objectId]);
    }
    // </editor-fold>
}
