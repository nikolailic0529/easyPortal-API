<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

use Throwable;

class RequestFailed extends RequestEvent {
    /**
     * @param array<mixed> $params
     */
    public function __construct(
        string $selector,
        string $query,
        array $params,
        protected mixed $response,
        protected Throwable|null $exception = null,
    ) {
        parent::__construct($selector, $query, $params);
    }

    public function getResponse(): mixed {
        return $this->response;
    }

    public function getException(): ?Throwable {
        return $this->exception;
    }
}
