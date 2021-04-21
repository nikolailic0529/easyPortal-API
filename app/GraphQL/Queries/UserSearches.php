<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Collection;

class UserSearches {
    public function __construct(protected AuthManager $auth) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): Collection {
        $user = $this->auth->user();
        return $user->searches()->where('key', $args['key'])->orderBy('created_at', 'desc')->get();
    }
}
