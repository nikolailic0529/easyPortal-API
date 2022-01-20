<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission as AuthPermission;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

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
        if (!$this->organization->defined()) {
            return false;
        }

        $organization = $this->organization->get();
        $available    = (new Collection($this->auth->getAvailablePermissions($organization)))
            ->map(static function (AuthPermission $permission): string {
                return $permission->getName();
            })
            ->all();
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
