<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Currency;
use Illuminate\Contracts\Validation\Rule;

use function __;

class CurrencyId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Currency::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.currency_id');
    }
}
