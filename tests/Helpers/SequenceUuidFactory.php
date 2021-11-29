<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class SequenceUuidFactory {
    protected int $start = 0;

    public function __invoke(): UuidInterface {
        return Uuid::uuid5('da788e31-1a09-4ba8-8dd3-016b3dc1db61', (string) $this->start++);
    }

    public function uuid(): string {
        return ($this)()->toString();
    }
}
