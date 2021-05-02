<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule;

use function __;
use function is_null;

class OrganizationId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return !is_null(Organization::find($value));
    }

    public function message(): string {
        return __('validation.organizationId');
    }
}
