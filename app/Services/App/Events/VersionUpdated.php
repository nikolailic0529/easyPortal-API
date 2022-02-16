<?php declare(strict_types = 1);

namespace App\Services\App\Events;

class VersionUpdated {
    public function __construct(
        private string $version,
        private ?string $previous,
    ) {
        // empty
    }

    public function getVersion(): string {
        return $this->version;
    }

    public function getPrevious(): ?string {
        return $this->previous;
    }
}
