<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use App\Exceptions\ExternalException;

class ServerError extends RequestFailed implements ExternalException {
    // empty
}
