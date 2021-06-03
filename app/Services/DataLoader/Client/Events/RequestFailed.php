<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

use Throwable;

class RequestFailed {
    /**
     * @param array<mixed> $request
     */
    public function __construct(
        protected array $request,
        protected mixed $response,
        protected Throwable|null $exception = null,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function getRequest(): array {
        return $this->request;
    }

    public function getResponse(): mixed {
        return $this->response;
    }

    public function getException(): ?Throwable {
        return $this->exception;
    }
}
