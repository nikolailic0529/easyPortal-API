<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs\Cron;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class Recalculator extends CronJob implements Progressable {
    /**
     * @phpstan-use \App\Services\Queue\Concerns\ProcessorJob<\App\Utils\Processor\EloquentProcessor>
     */
    use ProcessorJob;
}
