<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Document;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class ContractId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Document::query()->queryContracts()->whereKey($value)->exists();
    }

    public function message(): string {
        return trans('validation.contract_id');
    }
}
