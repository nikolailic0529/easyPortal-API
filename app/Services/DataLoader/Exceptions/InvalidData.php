<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contextable;

abstract class InvalidData extends DataLoaderException implements Contextable {
    // empty
}
