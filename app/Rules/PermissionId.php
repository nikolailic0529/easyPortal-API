<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Permission;
use Illuminate\Contracts\Validation\Rule;

use function __;

class PermissionId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Permission::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.permissions_id');
    }
}
