<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

class RequestSuccessful extends RequestEvent {
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        string $selector,
        string $query,
        array $variables,
        protected mixed $response,
    ) {
        parent::__construct($selector, $query, $variables);
    }

    public function getResponse(): mixed {
        return $this->response;
    }
}
