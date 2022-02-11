<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class SequenceUuidFactory {
    public function __construct(
        protected string $seed,
        protected int $start = 0,
    ) {
        // empty
    }

    public function __invoke(): UuidInterface {
        return Uuid::uuid5($this->seed, (string) $this->start++);
    }

    public function uuid(): string {
        return ($this)()->toString();
    }
}
