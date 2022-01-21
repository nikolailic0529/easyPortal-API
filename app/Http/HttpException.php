<?php declare(strict_types = 1);

namespace App\Http;

use App\Exceptions\ApplicationException;
use App\Exceptions\Contracts\TranslatedException;

abstract class HttpException extends ApplicationException implements TranslatedException {
    // empty
}
