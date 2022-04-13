<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Type;
use Illuminate\Contracts\Validation\Rule;

use function __;

class QuoteTypeId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Type::queryQuotes()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.quote_type_id');
    }
}
