<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Services\DataLoader\Container\Container;
use App\Utils\Processor\CompositeProcessor;
use App\Utils\Processor\State;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;

use function array_merge;

/**
 * Load data from API and create app's objects.
 *
 * @template TState of \App\Services\DataLoader\Loader\LoaderState
 *
 * @extends CompositeProcessor<TState>
 */
abstract class Loader extends CompositeProcessor {
    private string $objectId;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        protected Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContainer(): Container {
        return $this->container;
    }

    public function getObjectId(): string {
        return $this->objectId;
    }

    public function setObjectId(string $objectId): static {
        $this->objectId = $objectId;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Abstract">
    // =========================================================================
    abstract protected function getModelNotFoundException(string $id): Exception;
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new LoaderState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'objectId' => $this->getObjectId(),
            'started'  => Date::now(),
        ]);
    }
    // </editor-fold>
}
