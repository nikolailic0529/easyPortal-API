<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Queue\Concerns\DefaultConfig;
use App\Services\Queue\Concerns\PingableJob;
use App\Services\Queue\Contracts\NamedJob;
use App\Services\Queue\Contracts\Stoppable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob as LaraASPCronJob;

/**
 * Application Service.
 *
 * @see \App\Services\Queue\Contracts\Progressable
 */
abstract class CronJob extends LaraASPCronJob implements ShouldBeUnique, NamedJob, Stoppable {
    use PingableJob;
    use DefaultConfig;
}
