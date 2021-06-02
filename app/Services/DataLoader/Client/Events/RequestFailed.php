<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

use Throwable;

class RequestFailed {
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        protected array $data,
        protected mixed $json,
        protected Throwable|null $exception = null,
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

    public function getException(): ?Throwable {
        return $this->exception;
    }
}
