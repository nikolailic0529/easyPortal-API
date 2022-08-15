<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Services\Search\Processors\Concerns\WithModels;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeProcessor;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\State;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

use function array_map;

/**
 * Rebuild FULLTEXT indexes for Models.
 *
 * @extends CompositeProcessor<FulltextsProcessorState>
 */
class FulltextsProcessor extends CompositeProcessor {
    /**
     * @use WithModels<\Illuminate\Database\Eloquent\Model>
     */
    use WithModels;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Repository $config,
        private Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher, $config);
    }

    // <editor-fold desc="Description">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getOperations(CompositeState $state): array {
        return array_map(
            function (string $model): CompositeOperation {
                return new CompositeOperation($model, function () use ($model): FulltextProcessor {
                    return $this->container->make(FulltextProcessor::class)->setModel($model);
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
        return new FulltextsProcessorState($state);
    }
    // </editor-fold>
}
