<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Organization;
use App\Rules\Organization\EmailInvitable as OrganizationEmailInvitable;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;

class EmailInvitable extends OrganizationEmailInvitable {
    public function __construct(
        Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        parent::__construct($auth);
    }

    protected function getOrganization(): ?Organization {
        return $this->organization->defined()
            ? $this->organization->get()
            : null;
    }
}
