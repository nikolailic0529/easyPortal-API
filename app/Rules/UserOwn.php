<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Validation\Rule;

use function __;

class UserOwn implements Rule {
    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $this->auth->user()->getAuthIdentifier() !== $value;
    }

    public function message(): string {
        return __('validation.user_own');
    }
}
