<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Illuminate\Auth\AuthManager;

class DeleteMeSearches {
    public function __construct(protected AuthManager $auth) {
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
            $query = $query->where('key', $key);
        }
        $keys = $query->get();
        $query->delete();
        return [ 'deleted' => $keys->unique('key')->pluck('key') ];
    }
}
