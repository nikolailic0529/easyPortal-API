<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Illuminate\Auth\AuthManager;

use function array_unique;

class DeleteMeSearches {
    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }

    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $user  = $this->auth->user();
        $query = $user->searches();
        $key   = $args['input']['key'];

        if ($key !== null) {
            $query = $query->where('key', '=', $key);
        }
        $keys = [];
        foreach ($query->iterator()->safe() as $search) {
            if ($search->delete()) {
                $keys[] = $search->key;
            }
        }
        return [ 'deleted' => array_unique($keys) ];
    }
}
