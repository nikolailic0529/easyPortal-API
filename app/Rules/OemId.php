<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Oem;
use Illuminate\Contracts\Validation\Rule;

use function __;

class OemId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Oem::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.oem_id');
    }
}
