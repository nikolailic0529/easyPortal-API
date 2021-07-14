<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Queries\ContractTypes;
use App\Models\Document;
use Illuminate\Contracts\Validation\Rule;

use function __;

class ContractId implements Rule {
    public function __construct(
        protected ContractTypes $types,
    ) {
        // empty
    }
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        $builder = Document::whereKey($value);
        return $this->types->prepare($builder)->exists();
    }

    public function message(): string {
        return __('validation.contract_id');
    }
}
