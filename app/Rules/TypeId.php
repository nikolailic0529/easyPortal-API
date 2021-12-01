<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Type;
use Illuminate\Contracts\Validation\Rule;

use function __;

class TypeId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Type::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.type_id');
    }
}
