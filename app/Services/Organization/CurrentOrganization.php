<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use App\Services\Auth\Auth;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Services\Organization\Events\OrganizationChanged;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Contracts\Events\Dispatcher;

class CurrentOrganization extends OrganizationProvider {
    public function __construct(
        protected Dispatcher $dispatcher,
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
            // Set
            $previous = $this->getCurrent();
            $result   = false;
            $user     = $this->auth->getUser();

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

            // Event
            if ($result) {
                $current = $this->getCurrent();

                if ($previous?->getKey() !== $current?->getKey()) {
                    $this->dispatcher->dispatch(
                        new OrganizationChanged($previous, $current),
                    );
                }
            }

            // Return
            return $result;
        });
    }

    protected function getCurrent(): ?Organization {
        return $this->auth->getUser()?->getOrganization();
    }
}
