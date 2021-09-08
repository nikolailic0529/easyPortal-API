<?php declare(strict_types = 1);

namespace App\Services\Queue\Exceptions;

use App\Exceptions\TranslatedException;
use App\Services\Queue\ServiceException;
use Throwable;

use function __;
use function sprintf;

class ServiceNotFound extends ServiceException implements TranslatedException {
    public function __construct(
        protected string $service,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Service `%s` not found.', $this->service), $previous);
    }

    public function getErrorMessage(): string {
        return __('queue.errors.service_not_found', [
            'service' => $this->service,
        ]);
    }
}
