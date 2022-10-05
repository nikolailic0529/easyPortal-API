<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Data\ServiceLevel;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class ServiceLevelId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && ServiceLevel::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return trans('validation.service_level_id');
    }
}
