<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use function __;
use function filter_var;

use const FILTER_VALIDATE_FLOAT;

class FloatNumber implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    public function message(): string {
        return __('validation.float');
    }
}
