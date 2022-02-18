<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Exceptions;

use Throwable;

use function sprintf;

class InfiniteLoopDetected extends IteratorException {
    public function __construct(string $iterator, Throwable $previous = null) {
        parent::__construct(sprintf('Iterator `%s` seems to be in an infinite loop.', $iterator), $previous);
    }
}
