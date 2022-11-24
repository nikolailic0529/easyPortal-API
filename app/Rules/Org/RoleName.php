<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRuleImpl;
use App\Models\Role;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class RoleName implements Rule, ContextAwareRule {
    use ContextAwareRuleImpl;

    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        // Valid?
        if (!$value || !$this->organization->defined()) {
            return false;
        }

        // Context?
        if (!$this->hasMutationContext()) {
            return false;
        }

        // Exists?
        $role   = $this->getMutationRoot(Role::class) ?? new Role();
        $exists = $this->organization->get()
            ->roles()
            ->whereKeyNot($role->getKey())
            ->where($role->qualifyColumn('name'), '=', $value)
            ->exists();

        return $exists === false;
    }

    public function message(): string {
        return trans('validation.org_role_name');
    }
}
