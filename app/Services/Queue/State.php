<?php declare(strict_types = 1);

namespace App\Services\Queue;

use DateTimeInterface;

class State {
    public function __construct(
        public string $id,
        public string $name,
        public bool $running,
        public bool $stopped,
        public ?DateTimeInterface $pending_at,
        public ?DateTimeInterface $running_at,
    ) {
        // empty
    }
}
