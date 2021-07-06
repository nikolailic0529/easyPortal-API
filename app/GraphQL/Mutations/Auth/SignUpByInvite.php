<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Credential;
use App\Services\KeyCloak\Client\Types\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

use function array_key_exists;
use function json_decode;
use function json_encode;
use function time;

class SignUpByInvite {
    public function __construct(
        protected Client $client,
        protected Encrypter $encrypter,
        protected SignInOrganization $signInOrganization,
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
            throw new SignUpByInviteInvalidToken();
        }

        if (!array_key_exists('email', $data) || !array_key_exists('organization', $data)) {
            throw new SignUpByInviteInvalidToken();
        }

        // Get user from keycloak
        $user = $this->client->getUserByEmail($data['email']);

        if (!$user) {
            throw new SignUpByInviteInvalidUser();
        }

        if (!$user->attributes || !array_key_exists('ep_invite', $user->attributes)) {
            throw new SignUpByInviteUnInvitedUser();
        }

        $invitation = json_decode($user->attributes['ep_invite'][0]);
        if ($invitation->id) {
            throw new SignUpByInviteAlreadyUsed();
        }
        // update invitation
        $invitation->id      = $user->id;
        $invitation->used_at = time();

        // Create new credentials
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
                'ep_invite' => [json_encode($invitation)],
            ],
        ]));

        return $this->getSignInUri($data['organization']);
    }

    /**
     * @return array<string,string>
     */
    protected function getSignInUri(string $organization): array {
        $signInOrganization = $this->signInOrganization;
        return $signInOrganization(null, [
            'input' => [
                'organization_id' => $organization,
            ],
        ]);
    }
}
