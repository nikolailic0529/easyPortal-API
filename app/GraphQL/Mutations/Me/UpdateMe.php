<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\GraphQL\Mutations\Org\UpdateOrgUser;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Auth\AuthManager;

/**
 * @deprecated
 */
class UpdateMe {
    public function __construct(
        protected AuthManager $auth,
        protected Client $client,
        protected UpdateOrgUser $updateOrgUser,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $user = $this->auth->user();
        return [
            'result' => $this->updateOrgUser->updateUser($user, $args['input']),
        ];
    }
}
