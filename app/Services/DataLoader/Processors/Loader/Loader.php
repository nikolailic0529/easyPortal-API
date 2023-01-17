<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Processors\Loader\Concerns\WithLoaderState;
use App\Utils\Processor\CompositeProcessor;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Load data from API and create app's objects.
 *
 * @template TState of LoaderState
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
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContainer(): Container {
        return $this->container;
    }
    // </editor-fold>
}
