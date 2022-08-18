<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\QuoteRequestDuration;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class QuoteRequestDurationId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && QuoteRequestDuration::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return trans('validation.quote_request_duration_id');
    }
}
