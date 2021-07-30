<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Duration;
use Illuminate\Contracts\Validation\Rule;

use function __;

class DurationId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Duration::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.duration_id');
    }
}
