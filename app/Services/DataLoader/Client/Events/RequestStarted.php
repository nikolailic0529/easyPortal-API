<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

class RequestStarted {
    /**
     * @param array<mixed> $request
     */
    public function __construct(
        protected array $request,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function getRequest(): array {
        return $this->request;
    }
}
