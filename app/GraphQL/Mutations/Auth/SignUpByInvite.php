<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\Events\InvitationAccepted;
use App\GraphQL\Events\InvitationExpired;
use App\GraphQL\Events\InvitationOutdated;
use App\GraphQL\Events\InvitationUsed;
use App\GraphQL\Mutations\Auth\Organization\SignIn;
use App\GraphQL\Queries\Auth\Invitation as Query;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\Credential;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;

class SignUpByInvite {
    public function __construct(
        protected Dispatcher $dispatcher,
        protected Client $client,
        protected Query $query,
        protected SignIn $signIn,
    ) {
        // empty
    }

    /**
     * @param array{token:string,input?:array<string,mixed>} $args
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
            'org'    => $organization,
            'url'    => $result
                ? $this->signIn->getUrl($organization)
                : null,
        ];
    }

    protected function getInvitation(string $token): Invitation {
        // Invitation
        $invitation = $this->query->getInvitation($token);

        if (!$invitation) {
            throw new SignUpByInviteInvitationNotFound($token);
        }

        if ($this->query->isUsed($invitation)) {
            $this->dispatcher->dispatch(
                new InvitationUsed($invitation),
            );

            throw new SignUpByInviteInvitationUsed($invitation);
        }

        if ($this->query->isExpired($invitation)) {
            $this->dispatcher->dispatch(
                new InvitationExpired($invitation),
            );

            throw new SignUpByInviteInvitationExpired($invitation);
        }

        if ($this->query->isOutdated($invitation)) {
            $this->dispatcher->dispatch(
                new InvitationOutdated($invitation),
            );

            throw new SignUpByInviteInvitationOutdated($invitation);
        }

        // Return
        return $invitation;
    }

    protected function getOrganizationUser(Invitation $invitation): OrganizationUser {
        $user = GlobalScopes::callWithout(
            OwnedByScope::class,
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
        $result                    = $invitation->save() && $organizationUser->save();

        if ($result) {
            $this->dispatcher->dispatch(
                new InvitationAccepted($invitation),
            );
        }

        return $result;
    }
}
