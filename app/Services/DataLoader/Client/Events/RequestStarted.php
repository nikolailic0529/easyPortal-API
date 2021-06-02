<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

class RequestStarted {
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        protected array $data,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array {
        return $this->data;
    }
}
