<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Queue\Concerns\StoppableJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob as LaraASPCronJob;

/**
 * Application Service.
 *
 * @see \App\Services\Queue\Progressable
 */
abstract class CronJob extends LaraASPCronJob implements ShouldBeUnique, NamedJob, Stoppable {
    use StoppableJob;
}
