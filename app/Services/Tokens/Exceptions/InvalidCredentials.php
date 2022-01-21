<?php declare(strict_types = 1);

namespace App\Services\Tokens\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\Tokens\ServiceException;
use Throwable;

use function __;

class InvalidCredentials extends ServiceException implements TranslatedException {
    public function __construct(
        protected string $client,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "Invalid credentials for `{$this->client}`.",
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('tokens.invalid_credentials');
    }
}
