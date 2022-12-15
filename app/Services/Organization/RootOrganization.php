<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;

use function config;

class RootOrganization extends OrganizationProvider {
    protected Organization|null $current = null;

    public function getKey(): string {
        return $this->getRootKey() ?: $this->get()->getKey();
    }

    public function isRoot(): bool {
        return true;
    }

    protected function getCurrent(): ?Organization {
        if (!$this->current) {
            $id            = $this->getRootKey();
            $this->current = Organization::query()->whereKey($id)->first();
        }

        return $this->current;
    }

    protected function getRootKey(): ?string {
        return config('ep.root_organization');
    }
}
