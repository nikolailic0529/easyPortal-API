<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Events;

use App\Services\DataLoader\Schema\Type;
use Throwable;

class ObjectSkipped {
    public function __construct(
        protected Type $object,
        protected Throwable|null $reason = null,
    ) {
        // empty
    }

    public function getObject(): Type {
        return $this->object;
    }

    public function getReason(): ?Throwable {
        return $this->reason;
    }
}
