<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Jobs\NamedJob;
use LastDragon_ru\LaraASP\Queue\Queueables\Job as LaraASPJob;

abstract class Job extends LaraASPJob implements NamedJob {
    // empty
}
