<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\DataLoader\Events\ObjectSkipped;
use App\Services\Logger\LoggerObject;

class DataLoaderSkippedObject implements LoggerObject {
    public function __construct(
        protected ObjectSkipped $event,
    ) {
        // empty
    }

    public function getId(): ?string {
        return $this->event->getObject()->id ?? null;
    }

    public function getType(): string {
        return $this->event->getObject()->getName();
    }
}
