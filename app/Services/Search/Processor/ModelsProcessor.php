<?php declare(strict_types = 1);

namespace App\Services\Search\Processor;

use App\Services\Search\Eloquent\Searchable;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeProcessor;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\State;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

use function array_map;
use function array_merge;

/**
 * @extends CompositeProcessor<ModelsProcessorState>
 */
class ModelsProcessor extends CompositeProcessor {
    /**
     * @var array<int, class-string<Model&Searchable>>
     */
    protected array $models;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        private Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @param array<int, class-string<Model&Searchable>> $models
     */
    public function setModels(array $models): static {
        $this->models = $models;

        return $this;
    }

    /**
     * @return array<int, class-string<Model&Searchable>>
     */
    public function getModels(): array {
        return $this->models;
    }
    // </editor-fold>

    // <editor-fold desc="Description">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function operations(CompositeState $state): array {
        return array_map(
            function (string $model): CompositeOperation {
                return new CompositeOperation($model, function () use ($model): Processor {
                    return $this->container->make(Processor::class)->setModel($model)->setRebuild(true);
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
