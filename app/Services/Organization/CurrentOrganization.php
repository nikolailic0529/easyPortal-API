<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use App\Services\Auth\Auth;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;

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

    public function set(Organization $organization): bool {
        return GlobalScopes::callWithout(OwnedByScope::class, function () use ($organization): bool {
            $result = false;
            $user   = $this->getUser();

            if ($user) {
                $isMember = $user->getOrganizations()
                    ->contains(static function (Organization $org) use ($organization): bool {
                        return $org->is($organization);
                    });

                if ($isMember) {
                    $result = $user->setOrganization($organization);
                }
            }

            return $result;
        });
    }

    protected function getCurrent(): ?Organization {
        return $this->getUser()?->getOrganization();
    }

    protected function getUser(): ?HasOrganization {
        $user = $this->auth->getUser();
        $user = $user instanceof HasOrganization
            ? $user
            : null;

        return $user;
    }
}
