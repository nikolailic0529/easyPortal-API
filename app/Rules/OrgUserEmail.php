<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;

use function __;

class OrgUserEmail implements Rule {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        $user = User::query()->where('email', '=', $value)->first();
        if ($user) {
            $organization = $this->organization->get();
            return !$organization
                ->users()
                ->where($user->getQualifiedKeyName(), '=', $user->getKey())
                ->exists();
        }
        return true;
    }

    public function message(): string {
        return __('validation.invalid_org_user');
    }
}
