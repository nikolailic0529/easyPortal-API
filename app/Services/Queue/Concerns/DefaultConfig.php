<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Service;

/**
 * @mixin \App\Services\Queue\Job
 * @mixin \App\Services\Queue\CronJob
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
