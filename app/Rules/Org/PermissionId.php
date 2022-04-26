<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Concerns\AvailablePermissions;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;

use function __;

class PermissionId implements Rule {
    use AvailablePermissions;

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
        $available    = $this->getAvailablePermissions($organization);
        $exists       = Permission::query()
            ->whereKey($value)
            ->whereIn('key', $available)
            ->exists();

        return $exists;
    }

    public function message(): string {
        return __('validation.org_permission_id');
    }

    protected function getAuth(): Auth {
        return $this->auth;
    }
}
