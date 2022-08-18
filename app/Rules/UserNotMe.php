<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRule;
use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRuleImpl;
use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class UserNotMe implements Rule, ContextAwareRule {
    use ContextAwareRuleImpl;

    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        // todo(graphql): $value should be used as User ID
        $auth = $this->auth->user()?->getAuthIdentifier();
        $user = $value;

        if ($this->hasMutationContext()) {
            $user = $this->getMutationRoot(User::class)?->getKey();
        }

        return $user && $auth !== $user;
    }

    public function message(): string {
        return trans('validation.user_not_me');
    }
}
