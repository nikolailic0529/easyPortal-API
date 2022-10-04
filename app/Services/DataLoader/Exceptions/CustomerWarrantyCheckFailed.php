<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use Throwable;

use function sprintf;

class CustomerWarrantyCheckFailed extends WarrantyCheckFailed {
    public function __construct(string $key, string $reason = null, Throwable $previous = null) {
        parent::__construct(sprintf('Warranty Check for Customer `%s` failed.', $key), $previous);

        $this->setContext([
            'reason' => $reason,
        ]);
    }
}
