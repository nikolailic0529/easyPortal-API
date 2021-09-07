<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Role;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;

use function __;

class OrgRoleId implements Rule {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        $organization = $this->organization->get();
        return $organization
            ->roles()
            ->where((new Role())->getQualifiedKeyName(), '=', $value)
            ->exists();
    }

    public function message(): string {
        return __('validation.invalid_org_role');
    }
}
