<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Data\Oem;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class OemId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Oem::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return trans('validation.oem_id');
    }
}
