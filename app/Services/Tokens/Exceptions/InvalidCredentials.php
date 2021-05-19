<?php declare(strict_types = 1);

namespace App\Services\Tokens\Exceptions;

use Throwable;

use function __;

class InvalidCredentials extends TokenException {
    public function __construct(
        protected string $client,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "Invalid credentials for `{$this->client}`.",
            0,
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('tokens.invalid_credentials');
    }
}
