<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Queries\QuoteTypes;
use App\Models\Document;
use Illuminate\Contracts\Validation\Rule;

use function __;

class QuoteId implements Rule {
    public function __construct(
        protected QuoteTypes $types,
    ) {
        // empty
    }
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $this->types->prepare(Document::query()->whereKey($value))->exists();
    }

    public function message(): string {
        return __('validation.quote_id');
    }
}
