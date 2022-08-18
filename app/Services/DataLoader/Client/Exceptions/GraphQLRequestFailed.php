<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\Contracts\ExternalException;
use App\Exceptions\Contracts\TranslatedException;
use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function trans;

class GraphQLRequestFailed extends ClientException implements
    ExternalException,
    TranslatedException,
    IteratorFatalError {
    /**
     * @param array<string, mixed> $variables
     * @param array<mixed>         $errors
     */
    public function __construct(
        protected string $query,
        protected array $variables,
        protected array $errors = [],
        Throwable $previous = null,
    ) {
        parent::__construct('GraphQL request failed.', $previous);

        $this->setContext([
            'query'     => $this->query,
            'variables' => $this->variables,
            'errors'    => $this->errors,
        ]);
    }

    public function getErrorMessage(): string {
        return trans('data-loader.client.request_failed');
    }
}
