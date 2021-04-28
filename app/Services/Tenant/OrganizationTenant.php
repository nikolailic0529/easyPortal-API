<?php declare(strict_types = 1);

namespace App\Services\Tenant;

use App\Models\Organization;

/**
 * Encapsulates current organization.
 */
class OrganizationTenant implements Tenant {
    public function __construct(
        protected Organization $organization,
    ) {
        // empty
    }

    public function get(): Organization {
        return $this->organization;
    }

    public function getKey(): string {
        return $this->get()->getKey();
    }

    public function isRoot(): bool {
        return $this->get()->isRoot();
    }

    public function preferredLocale(): string|null {
        return $this->get()->preferredLocale();
    }

    public function is(Organization|string|null $tenant): bool {
        $equal = false;

        if ($tenant instanceof Organization) {
            $equal = $this->is($tenant->getKey());
        } else {
            $equal = $this->getKey() === $tenant;
        }

        return $equal;
    }
}
