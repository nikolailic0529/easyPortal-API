<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\Models\Permission;
use App\Services\Auth\Auth;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class PermissionId implements Rule {
    use HasOrganization;

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
        $available = $this->auth->getAvailablePermissions($organization);
        $exists    = Permission::query()
            ->whereKey($value)
            ->whereIn('key', $available)
            ->exists();

        return $exists;
    }

    public function message(): string {
        return trans('validation.organization_permissions_id');
    }
}
