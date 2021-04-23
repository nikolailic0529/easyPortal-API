<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Directives\RootDirective;
use App\Models\User;
use Illuminate\Auth\AuthManager;

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
        return $this->auth->user();
    }

    public function root(?User $user): bool {
        return $user && $this->root->isRoot($user);
    }
}
