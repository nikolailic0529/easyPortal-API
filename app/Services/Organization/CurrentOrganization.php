<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use App\Services\Auth\Auth;

class CurrentOrganization extends OrganizationProvider {
    public function __construct(
        protected RootOrganization $root,
        protected Auth $auth,
    ) {
        parent::__construct();
    }

    public function isRoot(): bool {
        return $this->root->is($this->get());
    }

    protected function getCurrent(): ?Organization {
        $user         = $this->auth->getUser();
        $organization = $user instanceof HasOrganization
            ? $user->getOrganization()
            : null;

        return $organization;
    }
}
