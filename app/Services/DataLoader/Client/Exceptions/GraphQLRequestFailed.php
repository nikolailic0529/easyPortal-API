<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\ExternalException;
use App\Exceptions\TranslatedException;
use Throwable;

use function __;

class GraphQLRequestFailed extends ClientException implements ExternalException, TranslatedException {
    /**
     * @param array<mixed> $params
     * @param array<mixed> $errors
     */
    public function __construct(
        protected string $query,
        protected array $params,
        protected array $errors = [],
        Throwable $previous = null,
    ) {
        parent::__construct('GraphQL request failed.', $previous);

        $this->setContext([
            'query'  => $this->query,
            'params' => $this->params,
            'errors' => $this->errors,
        ]);
    }

    public function getErrorMessage(): string {
        return __('dataloader.client.request_failed');
    }
}
