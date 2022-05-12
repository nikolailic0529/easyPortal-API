<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Concerns\WithObjectId;
use App\Services\DataLoader\Importer\Finders\AssetLoaderFinder;
use App\Services\DataLoader\Importer\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Importer\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\ImporterState;
use App\Services\DataLoader\Loader\Concerns\WithLoaderState;
use App\Utils\Processor\CompositeProcessor;
use Closure;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Load data from API and create app's objects.
 *
 * @template TState of \App\Services\DataLoader\Loader\LoaderState
 *
 * @extends CompositeProcessor<TState>
 */
abstract class Loader extends CompositeProcessor implements Isolated {
    use WithLoaderState;
    use WithObjectId;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        protected Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);

        $container->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $container->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $container->bind(CustomerFinder::class, CustomerLoaderFinder::class);
        $container->bind(AssetFinder::class, AssetLoaderFinder::class);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContainer(): Container {
        return $this->container;
    }
    // </editor-fold>

    // <editor-fold desc="Process">
    // =========================================================================
    abstract protected function getModelNotFoundException(string $id): Exception;

    /**
     * @return Closure(TState, bool): void
     */
    protected function getModelNotFoundHandler(): Closure {
        return function (LoaderState $state, bool $result): void {
            if ($result) {
                $current = $state->getCurrentOperationState();
                $result  = ($current instanceof ImporterState && $current->ignored === 0)
                    && $current->processed !== 0;
            }

            if (!$result) {
                throw $this->getModelNotFoundException($state->objectId);
            }
        };
    }
    // </editor-fold>
}
