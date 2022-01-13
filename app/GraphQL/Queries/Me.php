<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Audits\Audit;
use App\Models\OrganizationUser;
use App\Models\Team;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Me {
    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<mixed>|null
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): ?User {
        return $this->getMe($context->user());
    }

    public function root(?User $user): bool {
        return $this->auth->isRoot($user);
    }

    public function enabled(?User $user): bool {
        return $this->auth->isEnabled($user);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getMe(?Authenticatable $user): ?User {
        $me = null;

        if ($user instanceof User) {
            $me = $user;
        } elseif ($user) {
            $me                      = new User();
            $me->{$me->getKeyName()} = $user->getAuthIdentifier();
        } else {
            $me = null;
        }

        return $me;
    }

    /**
     * @deprecated
     * @return array<string,mixed>|null
     */
    public function profile(?User $user): ?array {
        if (!$user) {
            return null;
        }

        return [
            'given_name'     => $user->given_name,
            'family_name'    => $user->family_name,
            'office_phone'   => $user->office_phone,
            'contact_email'  => $user->contact_email,
            'title'          => $user->title,
            'academic_title' => $user->academic_title,
            'mobile_phone'   => $user->mobile_phone,
            'department'     => $user->department,
            'job_title'      => $user->job_title,
            'phone'          => $user->phone,
            'company'        => $user->company,
            'photo'          => $user->photo,
        ];
    }

    public function previousSignIn(?User $user): ?DateTimeInterface {
        $value = null;

        if ($user) {
            $value = GlobalScopes::callWithoutGlobalScope(
                OwnedByOrganizationScope::class,
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
}
