<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization as OrganizationModel;
use App\Services\Organization\Exceptions\UnknownOrganization;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Translation\HasLocalePreference;

class Organization implements HasLocalePreference {
    public function __construct(
        protected Factory $auth,
    ) {
        // empty
    }

    public function has(): bool {
        return (bool) $this->getCurrent();
    }

    public function get(): OrganizationModel {
        $organization = $this->getCurrent();

        if (!$organization) {
            throw new UnknownOrganization();
        }

        return $organization;
    }

    public function getKey(): string {
        return $this->get()->getKey();
    }

    public function isRoot(): bool {
        return $this->get()->isRoot();
    }

    public function preferredLocale(): ?string {
        return $this->get()->preferredLocale();
    }

    public function is(OrganizationModel|null $organization): bool {
        return $organization
            && $this->getKey() === $organization->getKey();
    }

    protected function getCurrent(): ?OrganizationModel {
        $user         = $this->auth->guard()->user();
        $organization = $user instanceof HasOrganization
            ? $user->getOrganization()
            : null;

        return $organization;
    }
}
