<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Jobs;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Services\Search\Processors\ModelProcessor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Rebuilds Search Index for Model.
 *
 * @template TItem of \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
 */
abstract class Indexer extends CronJob implements Progressable {
    /**
     * @phpstan-use ProcessorJob<ModelProcessor<TItem,\App\Services\Search\Processors\ModelProcessorState<TItem>>>
     */
    use ProcessorJob;

    /**
     * @inheritdoc
     */
    public function getQueueConfig(): array {
        return [
                'tries' => 5,
            ] + parent::getQueueConfig();
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): ModelProcessor {
        return $container
            ->make(ModelProcessor::class)
            ->setModel($this->getModel())
            ->setRebuild(true);
    }

    /**
     * @return class-string<TItem>
     */
    abstract protected function getModel(): string;
}
