<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\TranslatedException;
use Throwable;

use function __;

class DataLoaderDisabled extends ClientException implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('DataLoader disabled.', $previous);
    }

    public function getErrorMessage(): string {
        return __('dataloader.client.disabled');
    }
}
