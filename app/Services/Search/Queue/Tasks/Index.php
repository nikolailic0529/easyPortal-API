<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Job;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Processors\ModelProcessor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

/**
 * Update Search Index.
 *
 * @see \Laravel\Scout\Jobs\MakeSearchable
 * @see \Laravel\Scout\Jobs\RemoveFromSearch
 */
abstract class Index extends Job implements Initializable {
    /**
     * @phpstan-use ProcessorJob<ModelProcessor<Model&Searchable,\App\Services\Search\Processors\ModelProcessorState<Model&Searchable>>>
     */
    use ProcessorJob;

    /**
     * @private required for serialization
     * @var class-string<Model&Searchable>
     */
    protected string $model;

    /**
     * @private required for serialization
     * @var array<string|int>
     */
    protected array $keys;

    /**
     * @return class-string<Model&Searchable>
     */
    public function getModel(): string {
        return $this->model;
    }

    /**
     * @param class-string<Model&Searchable> $model
     */
    protected function setModel(string $model): static {
        $this->model = $model;

        return $this;
    }

    /**
     * @return array<string|int>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @param array<string|int> $keys
     */
    protected function setKeys(array $keys): static {
        $this->keys = $keys;

        return $this;
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): ModelProcessor {
        return $container
            ->make(ModelProcessor::class)
            ->setModel($this->getModel())
            ->setKeys($this->getKeys());
    }
}
