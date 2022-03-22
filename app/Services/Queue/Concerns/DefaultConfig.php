<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\CronJob;
use App\Services\Queue\Job;
use App\Services\Service;

/**
 * @mixin Job
 * @mixin CronJob
 */
trait DefaultConfig {
    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        $service = Service::getService($this);
        $queue   = $service ? $service::getDefaultQueue() : null;

        return [
                'queue' => $queue,
            ] + parent::getQueueConfig();
    }
}
