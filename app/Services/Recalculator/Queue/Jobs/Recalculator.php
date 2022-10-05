<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Jobs;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\EloquentState;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class Recalculator extends CronJob implements Progressable {
    /**
     * @phpstan-use ProcessorJob<EloquentProcessor<TModel,null,EloquentState<TModel>>>
     */
    use ProcessorJob;
}
