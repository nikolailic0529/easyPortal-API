<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Credential;
use App\Services\KeyCloak\Client\Types\User;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Routing\UrlGenerator;

use function array_key_exists;
use function strtr;

class UpdateOrgUserPassword {
    public function __construct(
        protected Client $client,
        protected Encrypter $encrypter,
        protected UrlGenerator $generator,
        protected Repository $config,
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
        $input = $args['input'];
        $data  = [];
        try {
            $data = $this->encrypter->decrypt($input['token']);
        } catch (DecryptException $e) {
            throw new UpdateOrgUserPasswordInvalidToken();
        }

        if (!array_key_exists('email', $data) || !array_key_exists('organization', $data)) {
            throw new UpdateOrgUserPasswordInvalidToken();
        }

        // Get user from keycloak
        $user = $this->client->getUserByEmail($data['email']);

        if (!$user) {
            throw new UpdateOrgUserPasswordInvalidUser();
        }

        if (
            !$user->attributes ||
            !array_key_exists('invited', $user->attributes) ||
            (int) $user->attributes['invited'][0] === 0
        ) {
            throw new UpdateOrgUserPasswordUnInvitedUser();
        }

        if (
            !array_key_exists('added_password_through_invite', $user->attributes) ||
            (int) $user->attributes['added_password_through_invite'][0] === 1
        ) {
            throw new UpdateOrgUserPasswordAlreadyAdded();
        }

        $credential = new Credential([
            'type'      => 'password',
            'temporary' => false,
            'value'     => $input['password'],
        ]);
        // update Profile
        $this->client->updateUser($user->id, new User([
            'firstName'     => $input['first_name'],
            'lastName'      => $input['last_name'],
            'enabled'       => true,
            'emailVerified' => true,
            'credentials'   => [
                $credential,
            ],
            'attributes'    => [
                'added_password_through_invite' => [true],
            ],
        ]));

        return ['result' => $this->getSignInUri($data['organization'])];
    }

    protected function getSignInUri(string $organization): string {
        return $this->generator->to(strtr($this->config->get('ep.client.organization_signin_uri'), [
            '{organization}' => $organization,
        ]));
    }
}
