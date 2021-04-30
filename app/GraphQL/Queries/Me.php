<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Directives\RootDirective;
use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;

class Me {
    public function __construct(
        protected AuthManager $auth,
        protected RootDirective $root,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<mixed>|null
     */
    public function __invoke(mixed $_, array $args): ?User {
        return $this->getMe($this->auth->guard()->user());
    }

    public function root(?User $user): bool {
        return $user && $this->root->isRoot($user);
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
