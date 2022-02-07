<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Services\DataLoader\Concerns\SafeChunkItemConverter;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Iterators\ObjectIteratorIterator;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * @template T of \App\Utils\Eloquent\Model|string
 * @template V of \App\Services\DataLoader\Schema\Type
 *
 * @extends \App\Utils\Iterators\ObjectIteratorIterator<T,V>
 */
class IteratorIterator extends ObjectIteratorIterator {
    use SafeChunkItemConverter;

    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        ObjectIterator $iterator,
        Closure $converter,
    ) {
        parent::__construct($iterator, $converter);
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }
}
