<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use function __;
use function in_array;

class Boolean implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return in_array($value, [true, false, 'true', 'false'], true);
    }

    public function message(): string {
        return __('validation.boolean');
    }
}
