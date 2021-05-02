<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Currency;
use Illuminate\Contracts\Validation\Rule;

use function __;
use function is_null;

class CurrencyId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return !is_null(Currency::find($value));
    }

    public function message(): string {
        return __('validation.currencyId');
    }
}
