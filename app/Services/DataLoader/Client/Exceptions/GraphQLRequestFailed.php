<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\Contextable;
use Throwable;

use function __;

class GraphQLRequestFailed extends ClientException implements Contextable {
    /**
     * @param array<mixed> $variables
     * @param array<mixed> $errors
     */
    public function __construct(
        protected string $query,
        protected array $variables,
        protected array $errors = [],
        Throwable $previous = null,
    ) {
        parent::__construct('GraphQL request failed.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('dataloader.client.request_failed');
    }

    /**
     * @inheritDoc
     */
    public function context(): array {
        return [
            'query'     => $this->query,
            'variables' => $this->variables,
            'errors'    => $this->errors,
        ];
    }
}