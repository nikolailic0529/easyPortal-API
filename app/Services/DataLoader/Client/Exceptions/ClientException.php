<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\TranslatedException;
use App\Services\DataLoader\ServiceException;

abstract class ClientException extends ServiceException implements TranslatedException {
    // empty
}
