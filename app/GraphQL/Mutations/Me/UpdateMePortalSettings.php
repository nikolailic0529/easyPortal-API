<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use Illuminate\Auth\AuthManager;

class UpdateMePortalSettings {

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
        $user = $this->auth->user();

        foreach ($args['input'] as $key => $value) {
            $user->{$key} = $value;
        }
        return ['result' => (bool) $user->save()];
    }
}
