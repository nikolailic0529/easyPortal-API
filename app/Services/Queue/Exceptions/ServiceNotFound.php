<?php declare(strict_types = 1);

namespace App\Services\Queue\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\Queue\ServiceException;
use Throwable;

use function sprintf;
use function trans;

class ServiceNotFound extends ServiceException implements TranslatedException {
    public function __construct(
        protected string $service,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Service `%s` not found.', $this->service), $previous);
    }

    public function getErrorMessage(): string {
        return trans('queue.errors.service_not_found', [
            'service' => $this->service,
        ]);
    }
}
