<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

class Status {
    public function __construct(
        public int $chunk = 0,
        public int $processed = 0,
        public int $failed = 0,
        public int $updated = 0,
        public int $created = 0,
    ) {
        // empty
    }
}
