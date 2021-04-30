<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use Throwable;

use function __;
use function implode;
use function sprintf;

class InsufficientData extends KeyCloakException {
    /**
     * @param array<string> $missed
     */
    public function __construct(
        protected array $missed,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Insufficient data to create/update user, missed: `%s`.',
            implode('`, `', $this->missed),
        ), 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('auth.failed');
    }
}
