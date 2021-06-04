<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\LoggerObject;
use Illuminate\Contracts\Queue\Job;

class QueueObject implements LoggerObject {
    public function __construct(
        protected Job $job,
    ) {
        // empty
    }

    public function getId(): string {
        return $this->job->uuid();
    }

    public function getType(): string {
        return ($this->job->payload()['displayName'] ?? '') ?: $this->job->getName();
    }
}
