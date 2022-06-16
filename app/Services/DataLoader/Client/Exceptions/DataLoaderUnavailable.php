<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\Contracts\ExternalException;
use App\Exceptions\Contracts\TranslatedException;
use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function __;

class DataLoaderUnavailable extends ClientException implements
    ExternalException,
    TranslatedException,
    IteratorFatalError {
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        protected string $query,
        protected array $variables,
        Throwable $previous = null,
    ) {
        parent::__construct('DataLoader unavailable.', $previous);

        $this->setContext([
            'query'     => $this->query,
            'variables' => $this->variables,
        ]);
    }

    public function getErrorMessage(): string {
        return __('data-loader.client.unavailable');
    }
}
