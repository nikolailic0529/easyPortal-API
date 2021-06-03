<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

class RequestSuccessful {
    /**
     * @param array<mixed> $request
     */
    public function __construct(
        protected array $request,
        protected mixed $response,
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
}
