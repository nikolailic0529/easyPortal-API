<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use Illuminate\Contracts\Auth\Factory;

class CurrentOrganization extends OrganizationProvider {
    public function __construct(
        protected RootOrganization $root,
        protected Factory $auth,
    ) {
        parent::__construct();
    }

    public function isRoot(): bool {
        return $this->root->is($this->get());
    }

    protected function getCurrent(): ?Organization {
        $user         = $this->auth->guard()->user();
        $organization = null;


        $user instanceof HasOrganization
            ? $user->getOrganization()
            : null;

        return $organization;
    }
}
