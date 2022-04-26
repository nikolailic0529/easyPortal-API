<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule;

use function __;

class Id implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Organization::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.organization_id');
    }
}
