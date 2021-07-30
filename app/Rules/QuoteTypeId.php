<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Queries\QuoteTypes;
use App\Models\Document;
use App\Models\Type;
use Illuminate\Contracts\Validation\Rule;

use function __;

class QuoteTypeId implements Rule {
    public function __construct(
        protected QuoteTypes $types,
    ) {
        // empty
    }
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        $builder = Type::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->whereKey($value);
        return $this->types->prepare($builder, (new Type())->getKeyName())->exists();
    }

    public function message(): string {
        return __('validation.quote_type_id');
    }
}
