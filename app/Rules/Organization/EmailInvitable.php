<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\Auth;
use Illuminate\Contracts\Validation\InvokableRule;

use function trans;

class EmailInvitable implements InvokableRule {
    use HasOrganization;

    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    protected function getOrganization(): ?Organization {
        return $this->getContextOrganization();
    }

    // <editor-fold desc="InvokableRule">
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function __invoke($attribute, $value, $fail): void {
        // Organization?
        $organization = $this->getOrganization();

        if (!$organization) {
            $fail(trans('validation.email_invitable.organization_unknown'));

            return;
        }

        // User?
        $user = User::query()->where('email', '=', $value)->first();

        if (!$user) {
            return;
        }

        // Disabled?
        if ($user->email_verified && !$user->isEnabled(null)) {
            $fail(trans('validation.email_invitable.user_disabled'));

            return;
        }

        // Root?
        if ($this->auth->isRoot($user) || $user->type === UserType::local()) {
            $fail(trans('validation.email_invitable.user_root'));

            return;
        }

        // Member?
        $orgUser = $user->organizations()
            ->where('organization_id', '=', $organization->getKey())
            ->first();

        if ($orgUser && !$orgUser->invited) {
            $fail(trans('validation.email_invitable.user_member'));

            return;
        }
    }
    // </editor-fold>
}
