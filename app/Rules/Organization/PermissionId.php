<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission as AuthPermission;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

use function __;

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
        // Organization?
        $organization = $this->getContextOrganization();

        if (!$organization) {
            return false;
        }

        // Permission?
        $available = (new Collection($this->auth->getAvailablePermissions($organization)))
            ->map(static function (AuthPermission $permission): string {
                return $permission->getName();
            })
            ->all();
        $exists    = Permission::query()
            ->whereKey($value)
            ->whereIn('key', $available)
            ->exists();

        return $exists;
    }

    public function message(): string {
        return __('validation.organization_permissions_id');
    }
}
