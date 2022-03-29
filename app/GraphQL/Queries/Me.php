<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Audits\Audit;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Team;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class Me {
    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function __invoke(): ?User {
        return $this->getMe($this->auth->getUser());
    }

    public function root(?User $user): bool {
        return $this->auth->isRoot($user);
    }

    public function enabled(?User $user): bool {
        $org     = $this->organization->defined() ? $this->organization->get() : null;
        $enabled = $this->auth->isEnabled($user, $org);

        return $enabled;
    }

    public function getMe(?Authenticatable $user): ?User {
        $me = null;

        if ($user instanceof User) {
            $me = $user;
        } elseif ($user) {
            $me = (new User())->setKey($user->getAuthIdentifier());
        } else {
            // empty
        }

        return $me;
    }

    public function previousSignIn(?User $user): ?DateTimeInterface {
        $value = null;

        if ($user) {
            $value = GlobalScopes::callWithout(
                OwnedByScope::class,
                static function () use ($user): ?DateTimeInterface {
                    return Audit::query()
                        ->where('user_id', '=', $user->getKey())
                        ->where('action', '=', Action::authSignedIn())
                        ->orderByDesc('created_at')
                        ->limit(1)
                        ->offset(1)
                        ->first()
                        ?->created_at;
                },
            );
        }

        return $value;
    }

    public function team(User $user): ?Team {
        $team = null;

        if ($this->organization->defined()) {
            $orgId   = $this->organization->getKey();
            $orgUser = $user->organizations
                ->first(static function (OrganizationUser $user) use ($orgId): bool {
                    return $user->organization_id === $orgId;
                });
            $team    = $orgUser?->team;
        }

        return $team;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function orgs(User $user): Collection {
        return GlobalScopes::callWithout(OwnedByScope::class, static function () use ($user): Collection {
            return $user->getOrganizations();
        });
    }
}
