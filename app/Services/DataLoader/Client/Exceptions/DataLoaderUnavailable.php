<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use Throwable;

use function __;

class DataLoaderUnavailable extends ClientException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('DataLoader unavailable.', $previous);
    }

    public function getErrorMessage(): string {
        return __('dataloader.client.unavailable');
    }
}
