<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use ArrayIterator;
use RecursiveIterator;

use function is_array;

/**
 * @extends ArrayIterator<array-key, JsonObject|mixed>
 * @implements RecursiveIterator<array-key, JsonObject|mixed>
 */
class RecursiveJsonObjectIterator extends ArrayIterator implements RecursiveIterator {
    /**
     * @param JsonObject|array<JsonObject>|array<mixed> $object
     */
    public function __construct(JsonObject|array $object) {
        parent::__construct($object instanceof JsonObject ? [$object] : $object);
    }

    public function hasChildren(): bool {
        return is_array($this->current()) || $this->current() instanceof JsonObject;
    }

    /**
     * @return RecursiveIterator<array-key, mixed>
     */
    public function getChildren(): RecursiveIterator {
        $current  = $this->current();
        $children = [];

        if ($current instanceof JsonObject) {
            $children = $current->getProperties();
        } elseif (is_array($current)) {
            $children = $current;
        } else {
            // empty
        }

        return new RecursiveJsonObjectIterator($children);
    }
}
