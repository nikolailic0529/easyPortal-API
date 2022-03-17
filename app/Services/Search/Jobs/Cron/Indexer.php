<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs\Cron;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Rebuilds Search Index for Model.
 *
 * @template TItem of \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
 */
abstract class Indexer extends CronJob implements Progressable {
    /**
     * @phpstan-use ProcessorJob<Processor<TItem,\App\Services\Search\Processor\State<TItem>>>
     */
    use ProcessorJob;

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container
            ->make(Processor::class)
            ->setModel($this->getModel())
            ->setRebuild(true);
    }

    /**
     * @return class-string<Model&Searchable>
     */
    abstract protected function getModel(): string;
}
