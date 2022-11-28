<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRuleImpl;
use App\Models\User;
use App\Services\Auth\Auth;
use Illuminate\Contracts\Validation\Rule;

use function is_scalar;
use function trans;

class UserNotRoot implements Rule, ContextAwareRule {
    use ContextAwareRuleImpl;

    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        // todo(graphql): $value should be used as User ID
        $user = $value;

        if ($this->hasMutationContext()) {
            $user = $this->getMutationRoot(User::class);
        } elseif (is_scalar($user)) {
            $user = User::query()->whereKey($user)->first();
        } else {
            $user = null;
        }

        return $user && !$this->auth->isRoot($user);
    }

    public function message(): string {
        return trans('validation.user_not_root');
    }
}
