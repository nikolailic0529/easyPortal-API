<?php declare(strict_types = 1);

namespace App\Services\Queue;

/**
 * Mark that Job can handle "stop" requests (realization must be provided by the job).
 *
 * @see \App\Services\Queue\Concerns\PingableJob
 */
interface Stoppable {
    // empty
}
