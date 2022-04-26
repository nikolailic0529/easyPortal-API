<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Document;
use Illuminate\Contracts\Validation\Rule;

use function __;

class QuoteId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Document::query()->queryQuotes()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.quote_id');
    }
}
