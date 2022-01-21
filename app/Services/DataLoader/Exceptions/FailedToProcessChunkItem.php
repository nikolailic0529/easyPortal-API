<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\GenericException;
use Psr\Log\LogLevel;
use Throwable;

class FailedToProcessChunkItem extends FailedToProcessObject implements GenericException {
    public function __construct(
        protected mixed $item,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to process chunk item.', $previous);

        $this->setLevel(LogLevel::ERROR);
        $this->setContext([
            'item' => $this->item,
        ]);
    }

    public function getItem(): mixed {
        return $this->item;
    }
}
