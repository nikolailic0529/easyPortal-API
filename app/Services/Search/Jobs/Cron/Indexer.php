<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs\Cron;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Services\Search\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Rebuilds Search Index for Model.
 */
abstract class Indexer extends CronJob implements Progressable {
    use ProcessorJob;

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container
            ->make(Processor::class)
            ->setModel($this->getModel())
            ->setRebuild(true);
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>
     */
    abstract protected function getModel(): string;
}
