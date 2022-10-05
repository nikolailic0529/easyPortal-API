<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use CallbackFilterIterator;
use IteratorAggregate;
use RecursiveIteratorIterator;
use Traversable;

use function is_object;

/**
 * Iterates over
 *
 * @implements IteratorAggregate<array-key, JsonObject>
 */
class JsonObjectIterator implements IteratorAggregate {
    /**
     * @param JsonObject|array<JsonObject>|array<mixed> $object
     */
    public function __construct(
        protected JsonObject|array $object,
    ) {
        // return
    }

    public function getIterator(): Traversable {
        return new CallbackFilterIterator(
            new RecursiveIteratorIterator(
                new RecursiveJsonObjectIterator($this->object),
                RecursiveIteratorIterator::SELF_FIRST,
            ),
            static function (mixed $current): bool {
                return is_object($current);
            },
        );
    }
}
