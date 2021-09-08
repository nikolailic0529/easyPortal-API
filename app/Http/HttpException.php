<?php declare(strict_types = 1);

namespace App\Http;

use App\Exceptions\ApplicationException;
use App\Exceptions\TranslatedException;

abstract class HttpException extends ApplicationException implements TranslatedException {
    // empty
}
