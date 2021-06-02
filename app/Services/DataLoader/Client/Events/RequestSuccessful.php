<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

class RequestSuccessful {
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        protected array $data,
        protected mixed $json,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array {
        return $this->data;
    }

    public function getJson(): mixed {
        return $this->json;
    }
}
