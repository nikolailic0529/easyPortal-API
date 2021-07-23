<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Customer;
use Illuminate\Contracts\Validation\Rule;

use function __;

class CustomerId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Customer::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.customer_id');
    }
}
