<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Directives\RootDirective;
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
    public function __invoke(mixed $_, array $args): ?array {
        /** @var \App\Models\User|null $user */
        $user = $this->auth->user();

        return $user ? [
            'id'          => $user->getKey(),
            'given_name'  => $user->given_name,
            'family_name' => $user->family_name,
            'locale'      => $user->locale,
            'root'        => $this->root->isRoot($user),
        ] : null;
    }
}
