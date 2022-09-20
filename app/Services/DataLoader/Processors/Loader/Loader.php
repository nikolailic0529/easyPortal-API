<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Processors\Finders\AssetLoaderFinder;
use App\Services\DataLoader\Processors\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Processors\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Processors\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Processors\Loader\Concerns\WithLoaderState;
use App\Utils\Processor\CompositeProcessor;
use Closure;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Load data from API and create app's objects.
 *
 * @template TState of \App\Services\DataLoader\Processors\Loader\LoaderState
 *
 * @extends CompositeProcessor<TState>
 */
abstract class Loader extends CompositeProcessor implements Isolated {
    use WithLoaderState;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Repository $config,
        protected Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher, $config);

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
                $result  = $current && $current->processed !== 0;
            }

            if (!$result) {
                throw $this->getModelNotFoundException($state->objectId);
            }
        };
    }
    // </editor-fold>
}
