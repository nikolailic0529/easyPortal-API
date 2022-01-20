<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRule;
use App\Models\Role;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

use function __;

class RoleId implements Rule, ContextAwareRule {
    use HasOrganization;

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        // Organization?
        $organization = $this->getContextOrganization();

        if (!$organization) {
            return false;
        }

        // Role?
        $foreignKey = $organization->roles()->getForeignKeyName();
        $exists     = Role::query()
            ->whereKey($value)
            ->where(static function (Builder $builder) use ($foreignKey, $organization): Builder {
                return $builder
                    ->orWhere($foreignKey, '=', $organization->getKey())
                    ->orWhereNull($foreignKey);
            })
            ->exists();

        return $exists;
    }

    public function message(): string {
        return __('validation.organization_role_id');
    }
}
