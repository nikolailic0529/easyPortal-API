<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Exceptions;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function sprintf;

class BrokenIteratorDetected extends IteratorException implements IteratorFatalError {
    public function __construct(string $iterator, Throwable $previous = null) {
        parent::__construct(sprintf('Iterator `%s` seems broken.', $iterator), $previous);
    }
}
