<?php declare(strict_types = 1);

namespace App\Services\Organization\Events;

use App\Models\Organization;

class OrganizationChanged {
    public function __construct(
        protected ?Organization $previous,
        protected ?Organization $current,
    ) {
        // empty
    }

    public function getPrevious(): ?Organization {
        return $this->previous;
    }

    public function getCurrent(): ?Organization {
        return $this->current;
    }
}
