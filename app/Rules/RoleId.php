<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Role;
use Illuminate\Contracts\Validation\Rule;

use function __;

class RoleId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Role::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.role_id');
    }
}
