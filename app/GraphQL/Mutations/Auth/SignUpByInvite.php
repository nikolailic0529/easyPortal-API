<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\Mutations\Auth\Organization\SignIn;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\Credential;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Date;

class SignUpByInvite {
    public function __construct(
        protected Client $client,
        protected Encrypter $encrypter,
        protected SignIn $signIn,
    ) {
        // empty
    }

    /**
     * @param array{token:string,input:array<string,mixed>} $args
     *
     * @return array{result: bool, url: ?string}
     */
    public function __invoke(mixed $root, array $args): array {
        // Objects
        $invite           = $this->getInvitation($args['token']);
        $user             = $this->getUser($invite);
        $organization     = $this->getOrganization($invite);
        $organizationUser = $this->getOrganizationUser($invite);

        // If User profile is filled we should redirect it to the Sing In
        if ($user->email_verified) {
            return $this->getResult($organization, $this->markAsUsed($invite, $organizationUser));
        }

        // Args?
        if (!isset($args['input'])) {
            return $this->getResult($organization, false);
        }

        // Update Keycloak user
        $input        = new SignUpByInviteInput($args['input']);
        $keyCloakUser = $this->client->getUserById($user->getKey());

        $this->client->updateUser($keyCloakUser->id, new KeycloakUser([
            'firstName'     => $input->given_name,
            'lastName'      => $input->family_name,
            'emailVerified' => true,
            'credentials'   => [
                new Credential([
                    'type'      => 'password',
                    'temporary' => false,
                    'value'     => $input->password,
                ]),
            ],
        ]));

        // Update Local user
        $user->given_name     = $input->given_name;
        $user->family_name    = $input->family_name;
        $user->email_verified = true;
        $user->save();

        // Return
        return $this->getResult($organization, $this->markAsUsed($invite, $organizationUser));
    }

    /**
     * @return array{result: bool, url: ?string}
     */
    protected function getResult(Organization $organization, bool $result): array {
        return [
            'result' => $result,
            'url'    => $result
                ? $this->signIn->getUrl($organization)
                : null,
        ];
    }

    protected function getInvitation(string $token): Invitation {
        // Id
        $id = null;

        try {
            $id = $this->encrypter->decrypt($token)['invitation'] ?? null;
        } catch (DecryptException $exception) {
            throw new SignUpByInviteTokenInvalid($token, $exception);
        }

        if (!$id) {
            throw new SignUpByInviteTokenInvalid($token);
        }

        // Invitation
        /** @var \App\Models\Invitation|null $invitation */
        $invitation = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($id): ?Invitation {
                return Invitation::query()->whereKey($id)->first();
            },
        );

        if (!$invitation) {
            throw new SignUpByInviteInvitationNotFound($id);
        }

        if ($invitation->used_at) {
            throw new SignUpByInviteInvitationUsed($invitation);
        }

        if ($invitation->expired_at->isPast()) {
            throw new SignUpByInviteInvitationExpired($invitation);
        }

        $last = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($invitation): ?Invitation {
                return Invitation::query()
                    ->where('organization_id', '=', $invitation->organization_id)
                    ->where('user_id', '=', $invitation->user_id)
                    ->orderByDesc('created_at')
                    ->first();
            },
        );

        if (!$invitation->is($last)) {
            throw new SignUpByInviteInvitationOutdated($invitation);
        }

        // Return
        return $invitation;
    }

    protected function getOrganizationUser(Invitation $invitation): OrganizationUser {
        $user = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($invitation): ?OrganizationUser {
                return OrganizationUser::query()
                    ->where('organization_id', '=', $invitation->organization_id)
                    ->where('user_id', '=', $invitation->user_id)
                    ->first();
            },
        );

        if (!($user instanceof OrganizationUser)) {
            throw new SignUpByInviteInvitationUserNotFound($invitation);
        }

        return $user;
    }

    protected function getUser(Invitation $invitation): User {
        $user = $invitation->user;

        if (!($user instanceof User)) {
            throw new SignUpByInviteInvitationUserNotFound($invitation);
        }

        return $user;
    }

    protected function getOrganization(Invitation $invitation): Organization {
        $organization = $invitation->organization;

        if (!($organization instanceof Organization)) {
            throw new SignUpByInviteInvitationOrganizationNotFound($invitation);
        }

        return $organization;
    }

    protected function markAsUsed(Invitation $invitation, OrganizationUser $organizationUser): bool {
        $invitation->used_at       = Date::now();
        $organizationUser->invited = false;

        return $invitation->save()
            && $organizationUser->save();
    }
}
