<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use Illuminate\Contracts\Config\Repository;

class RootOrganization extends OrganizationProvider {
    public function __construct(
        protected Repository $config,
    ) {
        parent::__construct();
    }

    public function getKey(): string {
        return $this->getRootKey() ?: $this->get()->getKey();
    }

    public function isRoot(): bool {
        return true;
    }

    protected function getCurrent(): ?Organization {
        $id           = $this->getRootKey();
        $organization = Organization::query()->whereKey($id)->first();

        return $organization;
    }

    protected function getRootKey(): ?string {
        return $this->config->get('ep.root_organization');
    }
}
