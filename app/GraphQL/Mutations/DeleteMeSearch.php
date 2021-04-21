<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\UserSearch;
use Illuminate\Auth\AuthManager;

class DeleteMeSearch {
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
        $user   = $this->auth->user();
        $search = UserSearch::where('id', $args['input']['id'])->where('user_id', $user->id)->first();

        if (!$search) {
            throw new DeleteMeSearchNotFound();
        }

        $search->delete();

        return ['deleted' => $args['input']['id'] ];
    }
}
