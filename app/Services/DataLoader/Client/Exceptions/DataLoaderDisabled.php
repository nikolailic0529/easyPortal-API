<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function __;

class DataLoaderDisabled extends ClientException implements TranslatedException, IteratorFatalError {
    public function __construct(Throwable $previous = null) {
        parent::__construct('DataLoader disabled.', $previous);
    }

    public function getErrorMessage(): string {
        return __('dataloader.client.disabled');
    }
}
