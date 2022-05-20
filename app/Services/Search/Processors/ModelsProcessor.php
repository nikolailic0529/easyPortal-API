<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Services\Search\Processors\Concerns\WithModels;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeProcessor;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\State;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

use function array_map;
use function array_merge;

/**
 * @extends CompositeProcessor<ModelsProcessorState>
 */
class ModelsProcessor extends CompositeProcessor {
    /**
     * @use WithModels<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>>
     */
    use WithModels;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        private Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

    // <editor-fold desc="Description">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getOperations(CompositeState $state): array {
        return array_map(
            function (string $model): CompositeOperation {
                return new CompositeOperation($model, function () use ($model): ModelProcessor {
                    return $this->container->make(ModelProcessor::class)->setModel($model)->setRebuild(true);
                });
            },
            $state->models,
        );
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new ModelsProcessorState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'models' => $this->getModels(),
        ]);
    }
    // </editor-fold>
}
