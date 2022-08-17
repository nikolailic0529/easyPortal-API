<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function trans;

class DataLoaderDisabled extends ClientException implements TranslatedException, IteratorFatalError {
    public function __construct(Throwable $previous = null) {
        parent::__construct('DataLoader disabled.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('data-loader.client.disabled');
    }
}
