<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\KeyCloak\ServiceException;

abstract class ClientException extends ServiceException implements TranslatedException {
    // empty
}
