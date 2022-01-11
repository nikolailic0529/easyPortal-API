<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Role;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;

use function __;

class RoleId implements Rule {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $this->organization->get()
            ->roles()
            ->where((new Role())->getQualifiedKeyName(), '=', $value)
            ->exists();
    }

    public function message(): string {
        return __('validation.org_role_id');
    }
}
