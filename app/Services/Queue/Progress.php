<?php declare(strict_types = 1);

namespace App\Services\Queue;

class Progress {
    public function __construct(
        public ?int $total,
        public int $value,
    ) {
        // empty
    }
}
