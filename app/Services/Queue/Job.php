<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Queue\Concerns\PingableJob;
use LastDragon_ru\LaraASP\Queue\Queueables\Job as LaraASPJob;

/**
 * Application Job.
 */
abstract class Job extends LaraASPJob implements NamedJob, Stoppable {
    use PingableJob;
}
