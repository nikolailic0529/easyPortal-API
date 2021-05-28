<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use Throwable;

use function __;

class DataLoaderDisabled extends ClientException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('DataLoader disabled.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('dataloader.client.disabled');
    }
}
