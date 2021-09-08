<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

use function __;

class UniqueUserEmail implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return !User::query()->where('email', '=', $value)->exists();
    }

    public function message(): string {
        return __('validation.unique_user_email');
    }
}
