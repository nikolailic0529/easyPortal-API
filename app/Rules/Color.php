<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use function is_string;
use function preg_match;
use function trans;

class Color implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return is_string($value) && preg_match('/^#[A-Fa-f0-9]{6}$/', $value) > 0;
    }

    public function message(): string {
        return trans('validation.color');
    }
}
