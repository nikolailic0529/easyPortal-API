<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Jobs\NamedJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob as LaraASPCronJob;

abstract class CronJob extends LaraASPCronJob implements ShouldBeUnique, NamedJob {
    // empty
}
