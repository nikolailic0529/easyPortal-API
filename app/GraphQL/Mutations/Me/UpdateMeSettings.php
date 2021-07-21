<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\User;
use Illuminate\Auth\AuthManager;

class UpdateMeSettings {

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
        // Possible?
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = $this->auth->user();

        if (!($user instanceof User)) {
            return [
                'result' => false,
            ];
        }

        // Update
        return [
            'result' => $user->forceFill($args['input'])->save(),
        ];
    }
}
