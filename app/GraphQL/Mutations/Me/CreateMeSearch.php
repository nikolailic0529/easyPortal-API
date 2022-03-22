<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\UserSearch;
use Illuminate\Auth\AuthManager;

class CreateMeSearch {
    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $search             = new UserSearch();
        $user               = $this->auth->user();
        $search->conditions = $args['input']['conditions'];
        $search->key        = $args['input']['key'];
        $search->name       = $args['input']['name'];
        $search->user       = $user;
        $search->save();

        return ['created' => $search];
    }
}
