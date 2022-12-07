<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use Exception;

class Interrupt extends Exception implements IteratorFatalError {
    // empty
}
