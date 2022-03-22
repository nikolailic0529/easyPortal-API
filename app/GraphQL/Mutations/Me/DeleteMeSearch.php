<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Services\Auth\Auth;

class DeleteMeSearch {
    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $user   = $this->auth->getUser();
        $search = $user->searches()->whereKey($args['input']['id'])->first();

        if ($search) {
            $search->delete();
        }

        return ['deleted' => (bool) $search];
    }
}
