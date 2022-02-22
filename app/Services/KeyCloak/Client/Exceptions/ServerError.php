<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use App\Exceptions\Contracts\ExternalException;
use App\Utils\Iterators\Contracts\IteratorFatalError;

class ServerError extends RequestFailed implements ExternalException, IteratorFatalError {
    // empty
}
