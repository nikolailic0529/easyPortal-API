<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use App\Services\I18n\Contracts\HasTimezonePreference;
use App\Services\Organization\Exceptions\UnknownOrganization;
use Illuminate\Contracts\Translation\HasLocalePreference;

abstract class OrganizationProvider implements HasLocalePreference, HasTimezonePreference {
    public function __construct() {
        // empty
    }

    public function defined(): bool {
        return (bool) $this->getCurrent();
    }

    public function get(): Organization {
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
        return false;
    }

    public function preferredLocale(): ?string {
        return $this->get()->preferredLocale();
    }

    public function preferredTimezone(): ?string {
        return $this->get()->preferredTimezone();
    }

    public function is(Organization|null $organization): bool {
        return $organization
            && $this->getKey() === $organization->getKey();
    }

    abstract protected function getCurrent(): ?Organization;
}
