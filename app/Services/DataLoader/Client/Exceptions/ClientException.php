<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use App\Services\DataLoader\Exceptions\DataLoaderException;

abstract class ClientException extends DataLoaderException implements TranslatedException {
    use HasErrorCode;
}
