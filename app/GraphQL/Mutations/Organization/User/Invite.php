<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Organization\User;

use App\GraphQL\Events\InvitationCreated;
use App\Models\Data\Team;
use App\Models\Enums\UserType;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OrganizationUserInvitation;
use App\Services\Auth\Auth;
use App\Services\Keycloak\Client\Client;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Date;

use function strtr;

class Invite {
    public function __construct(
        protected Repository $config,
        protected UrlGenerator $url,
        protected Encrypter $encrypter,
        protected Dispatcher $dispatcher,
        protected Client $client,
        protected Auth $auth,
    ) {
        // empty
    }

    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(Organization $root, array $args): bool {
        return $this->invite($root, new InviteInput($args['input']));
    }

    protected function invite(Organization $organization, InviteInput $input): bool {
        // Prepare
        $user = User::query()->where('email', '=', $input->email)->first();
        $role = Role::query()->whereKey($input->role_id)->firstOrFail();
        $team = isset($input->team_id)
            ? Team::query()->whereKey($input->team_id)->first()
            : null;

        // User?
        if ($user) {
            // Disabled User cannot be invited
            if ($user->email_verified && !$user->isEnabled(null)) {
                return false;
            }

            // Root/Local user cannot be member of organization
            if ($this->auth->isRoot($user) || $user->type === UserType::local()) {
                return false;
            }
        } else {
            $user                 = new User();
            $user->type           = UserType::keycloak();
            $user->enabled        = true;
            $user->email          = $input->email;
            $user->email_verified = false;
            $user->permissions    = [];
        }

        // Member?
        $orgUser = null;

        if ($user->exists) {
            $orgUser = $user->organizations()
                ->where('organization_id', '=', $organization->getKey())
                ->first();

            if ($orgUser && !$orgUser->invited) {
                // If the User is already a member of the Organization we should
                // not send an invitation.
                return false;
            }
        }

        // Create Keycloak User
        $keyCloakUser = null;

        if ($user->exists) {
            $keyCloakUser = $this->client->getUserById($user->getKey());
        } else {
            $keyCloakUser = $this->client->getUserByEmail($input->email)
                ?? $this->client->createUser($input->email, $role);
            $user->id     = $keyCloakUser->id;
        }

        if (!$keyCloakUser->enabled) {
            throw new InviteImpossibleKeycloakUserDisabled($keyCloakUser);
        }

        // Save
        $user->save();

        // Add to Organization
        $this->client->addUserToGroup($keyCloakUser, $role->getKey());

        if (!$orgUser) {
            $orgUser               = new OrganizationUser();
            $orgUser->organization = $organization;
            $orgUser->user         = $user;
            $orgUser->role         = $role;
            $orgUser->team         = $team;
            $orgUser->enabled      = true;
            $orgUser->invited      = true;

            $orgUser->save();
        }

        // Invitation
        $invitation               = new Invitation();
        $invitation->organization = $organization;
        $invitation->sender       = $this->auth->getUser();
        $invitation->user         = $user;
        $invitation->role         = $role;
        $invitation->team         = $team;
        $invitation->email        = $user->email;
        $invitation->used_at      = null;
        $invitation->expired_at   = Date::now()->add(
            $this->config->get('ep.invite_expire') ?? Constants::EP_INVITE_EXPIRE,
        );

        $invitation->save();

        // Send
        $token = $this->encrypter->encrypt([
            'invitation' => $invitation->getKey(),
        ]);
        $url   = $this->url->to(strtr(
            $this->config->get('ep.client.invite_uri') ?? Constants::EP_CLIENT_INVITE_URI,
            [
                '{token}' => $token,
            ],
        ));

        $user->notify(new OrganizationUserInvitation($invitation, $url));

        // Event
        $this->dispatcher->dispatch(
            new InvitationCreated($invitation),
        );

        // Return
        return true;
    }
}
