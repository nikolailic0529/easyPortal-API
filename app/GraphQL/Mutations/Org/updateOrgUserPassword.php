<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Credential;
use App\Services\KeyCloak\Client\Types\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

class UpdateOrgUserPassword {
    public function __construct(
        protected Client $client,
        protected Encrypter $encrypter,
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
        try {
            $email = $this->encrypter->decrypt($input['token']);
            // Get user from keycloak
            $user = $this->client->getUserByEmail($email);
            if (!$user) {
                throw new UpdateOrgUserPasswordInvalidUser();
            }
            // if user has credential then he already have a password
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
            ]));

            return ['result' => true];
        } catch (DecryptException $e) {
            throw new UpdateOrgUserPasswordInvalidToken();
        }
    }
}
