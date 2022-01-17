<?php declare(strict_types = 1);

namespace App\Services\Notificator;

use App\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class NotificationQueued extends Notification implements ShouldQueue {
    use Queueable;

    /**
     * @return array<string,string>
     */
    public function viaQueues(): array {
        $this->queue       = Queues::NOTIFICATOR;
        $this->afterCommit = true;

        return [];
    }
}
