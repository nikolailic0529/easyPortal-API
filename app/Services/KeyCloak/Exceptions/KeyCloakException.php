<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;

abstract class KeyCloakException extends Exception implements TranslatedException {
    use HasErrorCode;
}
