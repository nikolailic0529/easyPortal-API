<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\User;
use App\Services\Auth\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Me {
    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<mixed>|null
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): ?User {
        return $this->getMe($context->user());
    }

    public function root(?User $user): bool {
        return $this->auth->isRoot($user);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getMe(?Authenticatable $user): ?User {
        $me = null;

        if ($user instanceof User) {
            $me = $user;
        } elseif ($user) {
            $me                      = new User();
            $me->{$me->getKeyName()} = $user->getAuthIdentifier();
        } else {
            $me = null;
        }

        return $me;
    }
}
