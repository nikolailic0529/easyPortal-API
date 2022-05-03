<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Job;
use App\Services\Recalculator\Processor\ChunkData;
use App\Services\Recalculator\Processor\Processor;
use App\Utils\Processor\Contracts\Processor as ProcessorContract;
use App\Utils\Processor\EloquentState;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

/**
 * Recalculates properties of given models.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class Recalculate extends Job implements Initializable, ShouldBeUnique, ShouldBeUniqueUntilProcessing {
    /**
     * @phpstan-use ProcessorJob<Processor<TModel, ChunkData<TModel>, EloquentState<TModel>>>
     */
    use ProcessorJob {
        getProcessor as private createProcessor;
    }

    protected string $modelKey;

    public function getModelKey(): string {
        return $this->modelKey;
    }

    public function uniqueId(): string {
        return $this->getModelKey();
    }

    /**
     * @return $this<TModel>
     */
    public function init(string $key): static {
        // Initialize
        $this->modelKey = $key;

        $this->initialized();

        // Return
        return $this;
    }

    /**
     * @return Processor<TModel, ChunkData<TModel>, EloquentState<TModel>>
     */
    protected function getProcessor(Container $container, QueueableConfig $config): ProcessorContract {
        return $this
            ->createProcessor($container, $config)
            ->setKeys([$this->getModelKey()]);
    }
}
