<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\Queue\Job as QueueJob;
use App\Services\Queue\Queues;

abstract class Job extends QueueJob {
    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'queue' => Queues::DATA_LOADER,
            ] + parent::getQueueConfig();
    }
}
