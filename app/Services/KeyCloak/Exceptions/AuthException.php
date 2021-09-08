<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Exceptions\TranslatedException;
use App\Services\KeyCloak\ServiceException;

abstract class AuthException extends ServiceException implements TranslatedException {
    // empty
}
