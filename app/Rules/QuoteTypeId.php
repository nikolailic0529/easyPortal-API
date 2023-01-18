<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Scopes\DocumentTypeQuoteType;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class QuoteTypeId implements Rule {
    public function __construct(
        protected DocumentTypeQuoteType $scope,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && $this->scope->getTypeQuery()->whereKey($value)->exists();
    }

    public function message(): string {
        return trans('validation.quote_type_id');
    }
}
