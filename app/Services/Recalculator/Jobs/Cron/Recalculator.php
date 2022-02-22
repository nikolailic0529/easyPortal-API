<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs\Cron;

use App\Queues;
use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @uses     \App\Services\Queue\Concerns\ProcessorJob<\App\Utils\Processor\EloquentProcessor>
 */
abstract class Recalculator extends CronJob implements Progressable {
    use ProcessorJob;

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'queue' => Queues::RECALCULATOR,
            ] + parent::getQueueConfig();
    }
}
