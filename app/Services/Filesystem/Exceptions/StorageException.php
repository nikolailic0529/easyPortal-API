<?php declare(strict_types = 1);

namespace App\Services\Filesystem\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\Filesystem\ServiceException;

abstract class StorageException extends ServiceException implements TranslatedException {
    // empty
}
