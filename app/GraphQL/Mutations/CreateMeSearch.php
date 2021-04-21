<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\UserSearch;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Str;

class CreateMeSearch {
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
        $search             = new UserSearch();
        $user               = $this->auth->user();
        $search->conditions = $args['input']['conditions'];
        $search->key        = $args['input']['key'];
        $search->name       = $args['input']['name'];
        $search->id         = Str::uuid()->toString();

        $search = $user->searches()->save($search);
        return ['created' => $search];
    }
}
