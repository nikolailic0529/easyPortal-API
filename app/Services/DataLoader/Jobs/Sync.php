<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\Queue\Queues;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

abstract class Sync extends Job implements ShouldBeUnique, Initializable {
    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'queue' => Queues::SYNC,
            ] + parent::getQueueConfig();
    }
}
