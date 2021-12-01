<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\QuoteRequestDuration;
use Illuminate\Contracts\Validation\Rule;

use function __;

class QuoteRequestDurationId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return QuoteRequestDuration::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.quote_request_duration_id');
    }
}
