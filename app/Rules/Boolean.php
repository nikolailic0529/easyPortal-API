<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use function in_array;
use function trans;

class Boolean implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return in_array($value, [true, false, 'true', 'false'], true);
    }

    public function message(): string {
        return trans('validation.boolean');
    }
}
