<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Language;
use Illuminate\Contracts\Validation\Rule;

use function __;

class LanguageId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Language::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.language_id');
    }
}
