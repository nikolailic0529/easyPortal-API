<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Events;

use App\Services\DataLoader\Exceptions\InvalidData;
use App\Services\DataLoader\Schema\Type;

class ObjectSkipped {
    public function __construct(
        protected Type $object,
        protected InvalidData|null $reason = null,
    ) {
        // empty
    }

    public function getObject(): Type {
        return $this->object;
    }

    public function getReason(): ?InvalidData {
        return $this->reason;
    }
}
