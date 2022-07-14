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
            $user   = $this->auth->getUser();

            if ($user) {
                $isMember = $user->getOrganizations()
                    ->contains(static function (Organization $org) use ($organization): bool {
                        return $org->is($organization);
                    });

                if ($isMember) {
                    $result = $user->setOrganization($organization);

                    if ($result) {
                        $permissions = $this->auth->getOrganizationUserPermissions($organization, $user);
                        $result      = $user->setPermissions($permissions);
                    } else {
                        $user->setOrganization(null);
                        $user->setPermissions([]);
                    }
                }
            }

            return $result;
        });
    }

    protected function getCurrent(): ?Organization {
        return $this->auth->getUser()?->getOrganization();
    }
}
