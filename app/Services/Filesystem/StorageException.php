<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use App\Exceptions\TranslatedException;
use Exception;

abstract class StorageException extends Exception implements TranslatedException {
    // empty
}