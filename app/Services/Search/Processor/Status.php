<?php declare(strict_types = 1);

namespace App\Services\Search\Processor;

use DateTimeInterface;

class Status {
    public function __construct(
        public ?DateTimeInterface $from = null,
        public string|int|null $continue = null,
        public ?int $total = null,
        public int $processed = 0,
        public int $chunk = 0,
    ) {
        // empty
    }
}
