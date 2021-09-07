<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\ServiceException;
use Throwable;

use function sprintf;

class CompanyUnknownType extends ServiceException {
    public function __construct(
        protected string $key,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Company `%s` has unknown type.', $key), $previous);
    }

    public function getKey(): string {
        return $this->key;
    }
}
