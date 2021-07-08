<?php declare(strict_types = 1);

namespace App\Services\Queue\Events;

use Illuminate\Contracts\Queue\Job;

class JobStopped {
    public function __construct(
        protected Job $job,
    ) {
        // empty
    }

    public function getJob(): Job {
        return $this->job;
    }
}
