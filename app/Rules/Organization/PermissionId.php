<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Concerns\AvailablePermissions;
use Illuminate\Contracts\Validation\Rule;

use function __;

class PermissionId implements Rule {
    use HasOrganization;
    use AvailablePermissions;

    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        // Value?
        if (!$value) {
            return false;
        }

        // Organization?
        $organization = $this->getContextOrganization();

        if (!$organization) {
            return false;
        }

        // Permission?
        $available = $this->getAvailablePermissions($organization);
        $exists    = Permission::query()
            ->whereKey($value)
            ->whereIn('key', $available)
            ->exists();

        return $exists;
    }

    public function message(): string {
        return __('validation.organization_permissions_id');
    }

    protected function getAuth(): Auth {
        return $this->auth;
    }
}
