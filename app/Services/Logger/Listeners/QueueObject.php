<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\LoggerObject;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Arr;
use LastDragon_ru\LaraASP\Queue\Contracts\Cronable;

use function is_a;

class QueueObject implements LoggerObject {
    public function __construct(
        protected Job $job,
    ) {
        // empty
    }

    public function getId(): ?string {
        return $this->job->uuid();
    }

    public function getType(): string {
        return ($this->job->payload()['displayName'] ?? '') ?: $this->job->getName();
    }

    public function isCronable(): bool {
        $class    = Arr::get($this->job->payload(), 'data.commandName');
        $cronable = is_a($class, Cronable::class, true);

        return $cronable;
    }
}
