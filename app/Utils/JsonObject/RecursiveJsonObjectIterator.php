<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use ArrayIterator;
use RecursiveArrayIterator;
use RecursiveIterator;

use function is_array;

class RecursiveJsonObjectIterator extends ArrayIterator implements RecursiveIterator {
    /**
     * @param JsonObject|array<JsonObject>|array<string, mixed> $object
     */
    public function __construct(JsonObject|array $object) {
        parent::__construct(is_array($object) ? $object : $object->getProperties());
    }

    public function hasChildren(): bool {
        return is_array($this->current()) || $this->current() instanceof JsonObject;
    }

    public function getChildren(): RecursiveIterator {
        return new RecursiveArrayIterator($this->current());
    }
}
