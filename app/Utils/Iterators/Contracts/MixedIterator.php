<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Contracts;

/**
 * Pseudo-type for PHPStan.
 *
 * @see https://github.com/phpstan/phpstan/issues/3960
 *
 * @internal
 *
 * @extends ObjectIterator<mixed>
 */
interface MixedIterator extends ObjectIterator {
    // empty
}
