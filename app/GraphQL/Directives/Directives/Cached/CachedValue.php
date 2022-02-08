<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use DateTimeInterface;

class CachedValue {
    public function __construct(
        public DateTimeInterface $timestamp,
        public mixed $value,
    ) {
        // empty
    }
}
