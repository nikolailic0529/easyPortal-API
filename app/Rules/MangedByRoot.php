<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use App\Services\Auth\Auth;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Validation\Rule;

use function __;

class MangedByRoot implements Rule {
    public function __construct(
        protected Auth $auth,
        protected AuthManager $authManager,
    ) {
        // empty
    }
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        $user = User::query()->whereKey($value)->first();
        if (!($user instanceof User)) {
            return false;
        }

        return !($user->isRoot() && !$this->auth->isRoot($this->authManager->user()));
    }

    public function message(): string {
        return __('validation.manged_by_root');
    }
}
