<?php declare(strict_types = 1);

namespace App\Services\Tokens\Exceptions;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;

abstract class TokenException extends Exception implements TranslatedException {
    use HasErrorCode;
}
