<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Utils\Iterators\OffsetBasedObjectIterator;

class OffsetBasedIterator extends OffsetBasedObjectIterator {
    use IteratorErrorHandler;
}
