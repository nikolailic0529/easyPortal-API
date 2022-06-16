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
     * @param array<mixed> $params
     */
    public function __construct(
        protected string $query,
        protected array $params,
        Throwable $previous = null,
    ) {
        parent::__construct('DataLoader unavailable.', $previous);

        $this->setContext([
            'query'  => $this->query,
            'params' => $this->params,
        ]);
    }

    public function getErrorMessage(): string {
        return __('data-loader.client.unavailable');
    }
}
