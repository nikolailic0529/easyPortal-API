<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

class RequestSuccessful extends RequestEvent {
    /**
     * @param array<mixed> $params
     */
    public function __construct(
        string $selector,
        string $query,
        array $params,
        protected mixed $response,
    ) {
        parent::__construct($selector, $query, $params);
    }

    public function getResponse(): mixed {
        return $this->response;
    }
}
