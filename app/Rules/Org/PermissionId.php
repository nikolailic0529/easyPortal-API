<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;

use function __;

class PermissionId implements Rule {
    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        if (!$value || !$this->organization->defined()) {
            return false;
        }

        $organization = $this->organization->get();
        $available    = $this->auth->getAvailablePermissionsNames($organization);
        $exists       = Permission::query()
            ->whereKey($value)
            ->whereIn('key', $available)
            ->exists();

        return $exists;
    }

    public function message(): string {
        return __('validation.org_permission_id');
    }
}
