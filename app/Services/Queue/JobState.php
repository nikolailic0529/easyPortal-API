<?php declare(strict_types = 1);

namespace App\Services\Queue;

use DateTimeInterface;

class JobState {
    public function __construct(
        public string $name,
        public string $id,
        public bool $running,
        public bool $stopped,
        public ?DateTimeInterface $pending_at,
        public ?DateTimeInterface $running_at,
    ) {
        // empty
    }
}
