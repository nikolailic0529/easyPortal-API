<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Invitation;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Credential;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Date;

use function array_key_exists;

class SignUpByInvite {
    use GlobalScopes;

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

        if (!array_key_exists('invitation', $data)) {
            throw new SignUpByInviteInvalidToken();
        }

        $invitation = $this->callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($data) {
            return Invitation::whereKey($data['invitation'])->first();
        });

        if (!$invitation) {
            throw new SignUpByInviteInvalidToken();
        }
        /** @var \App\Models\Invitation $invitation */
        if ($invitation->expired_at->isPast()) {
            throw new SignUpByInviteExpired();
        }

        if ($invitation->used_at) {
            throw new SignUpByInviteAlreadyUsed();
        }

        // Get user from keycloak
        $keycloakUser = $this->client->getUserById($invitation->user_id);

        // Create new credentials
        $credential = new Credential([
            'type'      => 'password',
            'temporary' => false,
            'value'     => $input['password'],
        ]);

        // update local values
        $user                 = $invitation->user;
        $user->given_name     = $input['first_name'];
        $user->family_name    = $input['last_name'];
        $user->enabled        = true;
        $user->email_verified = true;
        $user->save();

        // update keycloak
        $this->client->updateUser($keycloakUser->id, new User([
            'firstName'     => $input['first_name'],
            'lastName'      => $input['last_name'],
            'enabled'       => true,
            'emailVerified' => true,
            'credentials'   => [
                $credential,
            ],
        ]));

        $invitation->used_at = Date::now();
        $invitation->save();
        return $this->getSignInUri($invitation->organization_id);
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
