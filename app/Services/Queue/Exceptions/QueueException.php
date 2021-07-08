<?php declare(strict_types = 1);

namespace App\Services\Queue\Exceptions;

use App\Exceptions\HasErrorCode;
use Exception;

abstract class QueueException extends Exception {
    use HasErrorCode;
}
