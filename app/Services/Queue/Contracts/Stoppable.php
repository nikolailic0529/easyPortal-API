<?php declare(strict_types = 1);

namespace App\Services\Queue\Contracts;

use App\Utils\Cache\CacheKeyable;
use Illuminate\Contracts\Queue\Job;

/**
 * Mark that Job can handle "stop" requests (realization must be provided by the job).
 *
 * @see \App\Services\Queue\Concerns\PingableJob
 */
interface Stoppable extends CacheKeyable {
    public function getJob(): ?Job;
}
