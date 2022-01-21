<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\Contracts\ExternalException;
use App\Exceptions\Contracts\TranslatedException;
use Throwable;

use function __;

class DataLoaderUnavailable extends ClientException implements ExternalException, TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('DataLoader unavailable.', $previous);
    }

    public function getErrorMessage(): string {
        return __('dataloader.client.unavailable');
    }
}
