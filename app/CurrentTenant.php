<?php declare(strict_types = 1);

namespace App;

use App\Models\Organization;
use LogicException;

/**
 * Encapsulates current tenant.
 */
class CurrentTenant {
    protected ?Organization $tenant = null;

    public function __construct() {
        // empty
    }

    public function has(): bool {
        return (bool) $this->tenant;
    }

    public function get(): Organization {
        return $this->tenant;
    }

    public function set(Organization $tenant): static {
        if ($this->tenant) {
            throw new LogicException('Tenant already defined, not possible to redefine it.');
        }

        $instance         = new static();
        $instance->tenant = $tenant;

        return $instance;
    }
}
