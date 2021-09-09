<?php declare(strict_types = 1);

namespace App\Services\Auth\Exceptions;

use App\Exceptions\TranslatedException;
use App\Services\Auth\ServiceException;

abstract class AuthException extends ServiceException implements TranslatedException {
    // empty
}
