<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Tasks;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Job;
use App\Services\Search\Processors\ModelProcessor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

/**
 * Update Search Index.
 *
 * @see \Laravel\Scout\Jobs\MakeSearchable
 * @see \Laravel\Scout\Jobs\RemoveFromSearch
 *
 * @phpstan-type SearchableModel \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
 */
abstract class Index extends Job implements Initializable {
    /**
     * @phpstan-use ProcessorJob<ModelProcessor<SearchableModel,\App\Services\Search\Processors\ModelProcessorState<SearchableModel>>>
     */
    use ProcessorJob;

    protected function makeProcessor(Container $container, QueueableConfig $config): ModelProcessor {
        return $container
            ->make(ModelProcessor::class)
            ->setModel($this->getModel())
            ->setKeys($this->getKeys());
    }

    /**
     * @return class-string<SearchableModel>
     */
    abstract protected function getModel(): string;

    /**
     * @return array<string|int>
     */
    abstract protected function getKeys(): array;
}
