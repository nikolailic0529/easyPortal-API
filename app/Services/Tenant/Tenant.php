<?php declare(strict_types = 1);

namespace App\Services\Tenant;

use App\Models\Organization;
use App\Services\Tenant\Exceptions\UnknownTenant;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Translation\HasLocalePreference;

class Tenant implements HasLocalePreference {
    public function __construct(
        protected Factory $auth,
    ) {
        // empty
    }

    public function has(): bool {
        return (bool) $this->getCurrent();
    }

    public function get(): Organization {
        $organization = $this->getCurrent();

        if (!$organization) {
            throw new UnknownTenant();
        }

        return $organization;
    }

    public function getKey(): string {
        return $this->get()->getKey();
    }

    public function preferredLocale(): ?string {
        return $this->get()->preferredLocale();
    }

    public function is(Organization|null $organization): bool {
        return $organization
            && $this->getKey() === $organization->getKey();
    }

    protected function getCurrent(): ?Organization {
        $user   = $this->auth->guard()->user();
        $tenant = $user instanceof Tenantable
            ? $user->getOrganization()
            : null;

        return $tenant;
    }
}
