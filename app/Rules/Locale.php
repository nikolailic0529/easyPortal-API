<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use ResourceBundle;

use function __;
use function in_array;

class Locale implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value) {
        return in_array($value, ResourceBundle::getLocales(''), true);
    }

    /**
     * @inheritdoc
     */
    public function message() {
        return __('validation.locale');
    }
}
