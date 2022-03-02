<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\Keycloak\ServiceException;

abstract class ClientException extends ServiceException implements TranslatedException {
    // empty
}
