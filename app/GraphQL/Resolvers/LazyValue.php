<?php declare(strict_types = 1);

namespace App\GraphQL\Resolvers;

use ArrayAccess;

class LazyValue implements ArrayAccess {
    /**
     * @param array<string,\Closure> $value
     */
    public function __construct(
        protected array $value,
    ) {
        // empty
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->value[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->value[$offset]);
    }
}
