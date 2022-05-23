<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use DateTimeInterface;

class CachedValue {
    public function __construct(
        public DateTimeInterface $created,
        public DateTimeInterface $expired,
        public ?float $time,
        public mixed $value,
    ) {
        // empty
    }
}
