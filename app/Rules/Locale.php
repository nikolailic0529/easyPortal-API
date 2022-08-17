<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use ResourceBundle;

use function in_array;
use function trans;

class Locale implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return in_array($value, ResourceBundle::getLocales(''), true);
    }

    public function message(): string {
        return trans('validation.locale');
    }
}
