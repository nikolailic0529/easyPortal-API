<?php declare(strict_types = 1);

namespace App\Services\Queue;

class Progress {
    /**
     * @param array<Progress> $operations
     */
    public function __construct(
        public ?string $name,
        public ?int $total,
        public ?int $value,
        public ?bool $current,
        public ?array $operations = null,
    ) {
        // empty
    }
}
