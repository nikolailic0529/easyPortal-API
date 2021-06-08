<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;

abstract class ClientException extends Exception implements TranslatedException {
    use HasErrorCode;
}
