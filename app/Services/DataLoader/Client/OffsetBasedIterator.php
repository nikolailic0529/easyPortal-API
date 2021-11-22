<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Utils\Iterators\OffsetBasedIterator as GraphQLOffsetBasedIterator;

class OffsetBasedIterator extends GraphQLOffsetBasedIterator {
    use IteratorErrorHandler;
}
