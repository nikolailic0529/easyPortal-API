<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Utils\Processor\State;

class Progress {
    /**
     * @param array<Progress> $operations
     */
    public function __construct(
        public ?State $state = null,
        public ?string $name = null,
        public ?bool $current = null,
        public ?array $operations = null,
    ) {
        // empty
    }

    public function __get(string $name): mixed {
        return $this->state->{$name} ?? null;
    }

    public function __isset(string $name): bool {
        return isset($this->state->{$name});
    }
}
