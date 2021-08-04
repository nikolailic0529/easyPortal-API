<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\ServiceLevel;
use Illuminate\Contracts\Validation\Rule;

use function __;

class ServiceLevelId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return ServiceLevel::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.service_level_id');
    }
}
